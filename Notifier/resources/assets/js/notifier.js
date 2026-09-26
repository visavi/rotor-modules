/**
 * Фоновая проверка новых личных сообщений
 *
 * Опрашивает сервер только одна вкладка, остальные получают результат через
 * BroadcastChannel. Лидер выбирается через Web Locks: блокировка держится, пока
 * вкладка жива, и освобождается браузером сама, если вкладку закрыли или она
 * упала — зависшего замка, как у localStorage-флага, тут не бывает.
 */
(() => {
    'use strict'

    const node = document.getElementById('js-notifier-config')
    if (!node) {
        return
    }

    let cfg
    try {
        cfg = JSON.parse(node.textContent)
    } catch {
        return
    }

    if (!cfg || !cfg.url || !cfg.interval) {
        return
    }

    const MAX_BACKOFF = 15 * 60 * 1000
    const MIN_GAP = 5000
    const ROOM = `notifier:${cfg.userId}`
    const LEASE_KEY = `${ROOM}:lease`
    const ANNOUNCE_KEY = `${ROOM}:announce`

    const baseTitle = document.title

    let known = Number(cfg.count) || 0
    let failures = 0
    let timer = null
    let stopped = false
    let exclusive = false
    let lastPoll = 0

    /* ------------------------------------------------------------------ */
    /* localStorage может быть недоступен в приватном режиме               */
    /* ------------------------------------------------------------------ */

    const readStore = (key) => {
        try {
            return localStorage.getItem(key)
        } catch {
            return null
        }
    }

    const writeStore = (key, value) => {
        try {
            localStorage.setItem(key, value)
        } catch {
            /* приватный режим или переполненное хранилище — молча пропускаем */
        }
    }

    /* ------------------------------------------------------------------ */
    /* Звук и уведомления браузера                                         */
    /* ------------------------------------------------------------------ */

    let audio = null
    let primed = false
    let desktopAsked = false

    if (cfg.sound && cfg.volume > 0) {
        audio = new Audio(cfg.sound)
        audio.preload = 'auto'
        audio.volume = cfg.volume
    }

    const desktopSupported = () => cfg.desktop && 'Notification' in window

    const needsGesture = () => (audio && !primed)
        || (desktopSupported() && !desktopAsked && Notification.permission === 'default')

    // Автозапуск звука без жеста пользователя запрещён, поэтому на первом же
    // клике проигрываем файл в тишине — дальше play() уже разрешён
    const primeAudio = () => {
        if (!audio || primed) {
            return
        }

        const volume = audio.volume
        audio.volume = 0

        audio.play().then(() => {
            audio.pause()
            audio.currentTime = 0
            audio.volume = volume
            primed = true
        }).catch(() => {
            audio.volume = volume
        })
    }

    const askDesktop = () => {
        if (!desktopSupported() || desktopAsked || Notification.permission !== 'default') {
            return
        }

        desktopAsked = true
        Notification.requestPermission().catch(() => {})
    }

    const GESTURES = ['pointerdown', 'keydown', 'touchstart']

    const onGesture = () => {
        primeAudio()
        askDesktop()

        if (!needsGesture()) {
            GESTURES.forEach((event) => document.removeEventListener(event, onGesture))
        }
    }

    if (needsGesture()) {
        GESTURES.forEach((event) => document.addEventListener(event, onGesture, { passive: true }))
    }

    const canAnnounce = () => (audio && primed)
        || (desktopSupported() && Notification.permission === 'granted')

    const announce = (count) => {
        if (audio && primed) {
            audio.currentTime = 0
            audio.play().catch(() => {})
        }

        if (!desktopSupported() || Notification.permission !== 'granted' || !document.hidden) {
            return
        }

        try {
            const notification = new Notification(cfg.texts.title, {
                body: String(cfg.texts.body).replace(':count', String(count)),
                tag: ROOM,
            })

            notification.onclick = () => {
                window.focus()
                notification.close()
                window.location.href = cfg.link
            }
        } catch {
            /* уведомления могут быть запрещены политикой браузера */
        }
    }

    // Звучать должна одна вкладка, иначе одно сообщение отзовётся хором.
    // Web Locks выдаёт блокировку ровно одному претенденту
    const announceOnce = (count) => {
        if (!canAnnounce()) {
            return
        }

        if (navigator.locks?.request) {
            navigator.locks.request(ANNOUNCE_KEY, { ifAvailable: true }, (lock) => {
                if (!lock) {
                    return null
                }

                announce(count)

                return new Promise((resolve) => setTimeout(resolve, 1500))
            }).catch(() => {})

            return
        }

        const raw = readStore(ANNOUNCE_KEY)
        const now = Date.now()

        if (raw) {
            try {
                const prev = JSON.parse(raw)

                if (prev.count === count && now - prev.at < 3000) {
                    return
                }
            } catch {
                /* мусор в хранилище — просто перезапишем */
            }
        }

        writeStore(ANNOUNCE_KEY, JSON.stringify({ count, at: now }))
        announce(count)
    }

    /* ------------------------------------------------------------------ */
    /* Отрисовка                                                           */
    /* ------------------------------------------------------------------ */

    const setCount = (count) => {
        if (typeof window.updateMessageCount === 'function') {
            window.updateMessageCount(count)

            return
        }

        document.querySelectorAll('.js-message-count').forEach((el) => {
            el.textContent = count || ''
        })
    }

    const setTitle = (count) => {
        if (!cfg.title) {
            return
        }

        document.title = count > 0 ? `(${count}) ${baseTitle}` : baseTitle
    }

    const apply = (value) => {
        const count = Number(value) || 0
        const grown = count > known

        known = count
        setCount(count)
        setTitle(count)

        if (grown) {
            announceOnce(count)
        }
    }

    /* ------------------------------------------------------------------ */
    /* Обмен между вкладками                                               */
    /* ------------------------------------------------------------------ */

    const channel = 'BroadcastChannel' in window ? new BroadcastChannel(ROOM) : null

    const publish = (count) => {
        apply(count)

        if (channel) {
            channel.postMessage({ type: 'count', count })

            return
        }

        // Событие storage не возникает, если значение не изменилось,
        // поэтому к счётчику добавляется отметка времени
        writeStore(ROOM, JSON.stringify({ count, at: Date.now() }))
    }

    const stop = () => {
        stopped = true
        clearTimeout(timer)
    }

    const schedule = (delay) => {
        clearTimeout(timer)

        if (stopped) {
            return
        }

        timer = setTimeout(poll, delay)
    }

    const pollNow = () => {
        if (stopped) {
            return
        }

        schedule(Math.max(0, MIN_GAP - (Date.now() - lastPoll)))
    }

    if (channel) {
        channel.onmessage = (event) => {
            const data = event.data

            if (!data) {
                return
            }

            if (data.type === 'count') {
                apply(data.count)
            } else if (data.type === 'stop') {
                stop()
            } else if (data.type === 'wake' && exclusive) {
                pollNow()
            }
        }
    } else {
        window.addEventListener('storage', (event) => {
            if (event.key !== ROOM || !event.newValue) {
                return
            }

            try {
                apply(JSON.parse(event.newValue).count)
            } catch {
                /* чужая или битая запись */
            }
        })
    }

    /* ------------------------------------------------------------------ */
    /* Опрос                                                               */
    /* ------------------------------------------------------------------ */

    // Запасной вариант для браузеров без Web Locks: аренда сама протухает,
    // поэтому закрытая на полуслове вкладка не останавливает опрос навсегда
    const claimLease = () => {
        if (exclusive) {
            return true
        }

        const now = Date.now()

        if (Number(readStore(LEASE_KEY)) > now) {
            return false
        }

        writeStore(LEASE_KEY, String(now + cfg.interval - 1000))

        return true
    }

    async function poll() {
        if (stopped) {
            return
        }

        if (!navigator.onLine || !claimLease()) {
            schedule(cfg.interval)

            return
        }

        lastPoll = Date.now()

        try {
            const response = await fetch(cfg.url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            })

            if (!response.ok) {
                throw new Error(String(response.status))
            }

            const data = await response.json()
            failures = 0

            if (data.auth === false) {
                channel?.postMessage({ type: 'stop' })
                stop()

                return
            }

            publish(data.count)
            schedule(cfg.interval)
        } catch {
            failures += 1
            schedule(Math.min(cfg.interval * 2 ** failures, MAX_BACKOFF))
        }
    }

    document.addEventListener('visibilitychange', () => {
        if (document.hidden || stopped) {
            return
        }

        if (exclusive || !channel) {
            pollNow()
        } else {
            channel.postMessage({ type: 'wake' })
        }
    })

    window.addEventListener('online', () => {
        failures = 0
        pollNow()
    })

    // Бейдж и заголовок сразу по числу из затравки, не дожидаясь первого опроса
    setCount(known)
    setTitle(known)

    if (navigator.locks?.request) {
        navigator.locks.request(`${ROOM}:leader`, () => {
            exclusive = true
            schedule(cfg.interval)

            // Промис намеренно не завершается: блокировка удерживается до
            // закрытия вкладки, после чего лидером становится следующая
            return new Promise(() => {})
        }).catch(() => schedule(cfg.interval))
    } else {
        schedule(cfg.interval)
    }
})()
