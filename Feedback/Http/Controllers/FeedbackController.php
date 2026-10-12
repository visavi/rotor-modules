<?php

declare(strict_types=1);

namespace Modules\Feedback\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Flood;
use App\Models\User;
use App\Services\MailService;
use App\Support\HtmlSanitizer;
use App\Support\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Feedback\Support\Feedback;

class FeedbackController extends Controller
{
    /**
     * Форма обратной связи
     */
    public function index(): View
    {
        $user = getUser();

        return view('feedback::index', [
            'asksName'  => ! $user,
            'asksEmail' => Feedback::asksEmail($user),
        ]);
    }

    /**
     * Отправка обращения: в личку владельцу или письмом, как выбрано в настройках
     */
    public function send(Request $request, Validator $validator, Flood $flood, MailService $mail): RedirectResponse
    {
        $user = getUser();
        $message = $request->string('message')->value();
        $name = $user ? $user->login : $request->string('name')->trim()->value();
        $email = Feedback::asksEmail($user) ? $request->string('email')->trim()->value() : (string) $user?->email;

        $validator
            ->true(captchaVerify(), ['protect' => __('validator.captcha')])
            ->false($flood->isFlood(), ['message' => __('validator.flood', ['sec' => $flood->getPeriod()])])
            ->length($message, 5, 50000, ['message' => __('validator.text')]);

        if (! $user) {
            $validator->length($name, 3, 100, ['name' => __('feedback::feedback.name_short_or_long')]);
        }

        if (Feedback::asksEmail($user)) {
            $validator->email($email, ['email' => __('validator.email')]);
        }

        if (! $validator->isValid()) {
            return redirect()->route('feedback.index')
                ->withInput()
                ->withErrors($validator->getErrors());
        }

        if (Feedback::byEmail()) {
            $this->sendEmail($mail, $message, $name, $email);
        } else {
            $this->sendMessages($user, $message, $name, $email);
        }

        $flood->saveState();

        return redirect()->route('feedback.index')
            ->with('success', __('feedback::feedback.success_sent'));
    }

    /**
     * Личным сообщением владельцу сайта
     *
     * Пользователю владелец отвечает в его же диалоге, поэтому имя и email
     * в тексте нужны только у гостя. Себе владелец (проверка формы) получает
     * системное сообщение: диалог с самим собой не открывается
     */
    private function sendMessages(?User $user, string $message, string $name, string $email): void
    {
        $owners = User::query()->where('level', User::BOSS)->get();

        foreach ($owners as $owner) {
            $locale = $owner->language ?: setting('language');
            $author = $user?->id === $owner->id ? null : $user;
            $sender = $author ? [] : [$name, $email];

            $owner->sendMessage($author, $this->composeText($locale, $message, ...$sender), false);
        }
    }

    /**
     * Письмом на адрес из настроек, ответ — кнопкой «Ответить» на адрес отправителя
     */
    private function sendEmail(MailService $mail, string $message, string $name, string $email): void
    {
        $locale = setting('language');

        // Письмо не проходит через каст модели: чистим здесь. Относительные
        // картинки (стикеры) в почтовом клиенте не откроются — делаем абсолютными
        $message = str_replace('src="/', 'src="' . url('/') . '/', HtmlSanitizer::sanitize($message));

        $mail->queue('mailer.default', [
            'to'      => Feedback::recipient(),
            'subject' => __('feedback::feedback.email_subject', ['site' => setting('title')], $locale),
            'text'    => $this->composeText($locale, $message, $name, $email),
            'from'    => [$email, $name],
        ]);
    }

    /**
     * Текст обращения со сведениями об отправителе
     */
    private function composeText(string $locale, string $message, ?string $name = null, ?string $email = null): string
    {
        $info = [];

        if ($name !== null) {
            $info[] = __('feedback::feedback.name', [], $locale) . ': ' . e($name);
        }

        if ($email !== null && $email !== '') {
            $info[] = 'Email: <a href="mailto:' . e($email) . '">' . e($email) . '</a>';
        }

        $info[] = 'IP: ' . e(getIp());
        $info[] = __('feedback::feedback.browser', [], $locale) . ': ' . e(getBrowser());

        return '<p><strong>' . __('feedback::feedback.message_from_form', [], $locale) . '</strong></p>'
            . $message
            . '<p>' . implode('<br>', $info) . '</p>';
    }
}
