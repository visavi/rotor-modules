<?php

declare(strict_types=1);

namespace Modules\Backup\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Backup\Http\Requests\BackupRequest;

class BackupController extends AdminController
{
    public string $date;

    /**
     * Конструктор
     */
    public function __construct()
    {
        if (function_exists('set_time_limit')) {
            set_time_limit(600);
        }

        $this->date = now()->format('d-M-Y_H-i-s');
    }

    /**
     * Главная страница
     */
    public function index(): View
    {
        $files = glob(storage_path('backups/*.{zip,gz,bz2,sql}'), GLOB_BRACE);
        arsort($files);

        return view('backup::admin/backups/index', compact('files'));
    }

    /**
     * Создание нового бэкапа
     */
    public function create(): View
    {
        $tables = DB::select('SHOW TABLE STATUS');

        $bzopen = function_exists('bzopen');
        $gzopen = function_exists('gzopen');

        $levels = range(0, 9);

        return view('backup::admin/backups/create', compact('tables', 'bzopen', 'gzopen', 'levels'));
    }

    /**
     * Сохранение бэкапа
     */
    public function store(BackupRequest $request): RedirectResponse
    {
        $sheets = $request->validated('sheets');
        $method = $request->string('method')->value();
        $level = $request->integer('level');

        $placeholders = implode(',', array_fill(0, count($sheets), '?'));
        $selectTables = DB::select('SHOW TABLE STATUS WHERE name IN(' . $placeholders . ')', (array) $sheets);

        $filename = 'backup_' . $this->date . '.sql';

        $fp = $this->fopen(storage_path('backups/' . $filename), 'w', $method, $level);

        // Одна транзакция — один снимок InnoDB на все чтения, как mysqldump --single-transaction:
        // записи, вставленные во время бэкапа, не сдвигают страницы и не попадают в дамп наполовину
        DB::transaction(function () use ($selectTables, $fp, $method) {
            foreach ($selectTables as $table) {
                $this->dumpTable($fp, $table, $method);
            }
        });

        $this->fclose($fp, $method);

        return redirect('admin/backups')
            ->with('success', __('backup::backup.database_success_saved'));
    }

    /**
     * Удаляет сохраненный бэкап
     */
    public function delete(Request $request): RedirectResponse
    {
        $file = $request->string('file')->value();
        $fullPath = realpath(storage_path('backups') . '/' . $file);

        $error = match (true) {
            $file === ''                                              => __('backup::backup.backup_not_indicated'),
            ! preg_match('#^[\w\-]+\.(sql\.)?(gz|bz2|sql)$#i', $file) => __('backup::backup.invalid_backup_name'),
            $fullPath === false || ! is_file($fullPath)               => __('backup::backup.backup_not_exist'),
            default                                                   => null,
        };

        if ($error) {
            return redirect('admin/backups')->withErrors($error);
        }

        unlink($fullPath);

        return redirect('admin/backups')
            ->with('success', __('backup::backup.backup_success_deleted'));
    }

    /**
     * Пишет в дамп структуру таблицы и её строки
     *
     * @param resource $fp
     */
    private function dumpTable($fp, object $table, string $method): void
    {
        $limit = 3000;
        $pdo = DB::getPdo();

        $show = DB::selectOne("SHOW CREATE TABLE `{$table->Name}`");
        $fields = array_column(DB::select("SHOW COLUMNS FROM `{$table->Name}`"), 'Field');

        $this->fwrite($fp, "--\n-- Structure table `{$table->Name}`\n--\n\n", $method);
        $isView = $table->Engine === null;
        $createSql = $isView ? $show->{'Create View'} : $show->{'Create Table'};
        $dropSql = $isView ? "DROP VIEW IF EXISTS `{$table->Name}`" : "DROP TABLE IF EXISTS `{$table->Name}`";
        $this->fwrite($fp, "{$dropSql};\n{$createSql};\n\n", $method);

        if ($isView) {
            return;
        }

        $total = DB::table($table->Name)->count();

        if (! $total) {
            return;
        }

        // Колонки в кавычках: имя вроде key или order иначе ломает восстановление
        $columns = '(' . implode(',', array_map(static fn (string $field) => '`' . $field . '`', $fields)) . ')';

        $this->fwrite($fp, "--\n-- Dump table `{$table->Name}`\n--\n\n", $method);
        $this->fwrite($fp, "INSERT INTO `{$table->Name}` {$columns} VALUES ", $method);

        // Без ORDER BY страницы LIMIT/OFFSET могут пересекаться: строки задваиваются или теряются
        $query = DB::table($table->Name);

        foreach ($this->orderColumns($table->Name) ?: $fields as $field) {
            $query->orderBy($field);
        }

        for ($i = 0; $i < $total; $i += $limit) {
            foreach ($query->clone()->limit($limit)->offset($i)->get() as $key => $row) {
                $values = [];

                foreach (get_object_vars($row) as $value) {
                    // Строку экранирует само соединение: замена ' на '' оставляла обратную
                    // косую черту, и дамп со значением вроде «C:\path\» не восстанавливался
                    $values[] = match (true) {
                        $value === null => 'null',
                        is_int($value)  => $value,
                        default         => $pdo->quote((string) $value),
                    };
                }

                $this->fwrite($fp, ($key || $i ? ',' : '') . '(' . implode(',', $values) . ')', $method);
            }
        }

        $this->fwrite($fp, ";\n\n", $method);
    }

    /**
     * Колонки первичного ключа по порядку, пусто — ключа нет
     *
     * @return list<string>
     */
    private function orderColumns(string $table): array
    {
        $keys = DB::select("SHOW KEYS FROM `{$table}` WHERE Key_name = 'PRIMARY'");

        usort($keys, static fn (object $a, object $b) => $a->Seq_in_index <=> $b->Seq_in_index);

        return array_column($keys, 'Column_name');
    }

    /**
     * Открывает поток
     *
     * @return bool|resource
     */
    private function fopen(string $name, string $mode, string $method, int $level)
    {
        if ($method === 'bzip') {
            return bzopen($name . '.bz2', $mode);
        }

        if ($method === 'gzip') {
            return gzopen($name . '.gz', "{$mode}b{$level}");
        }

        return fopen($name, $mode . 'b');
    }

    /**
     * Записывает данные в поток
     *
     * @param resource $fp
     */
    private function fwrite($fp, string $str, string $method): void
    {
        if ($method === 'bzip') {
            bzwrite($fp, $str);
        } elseif ($method === 'gzip') {
            gzwrite($fp, $str);
        } else {
            fwrite($fp, $str);
        }
    }

    /**
     * Закрывает поток
     *
     * @param resource $fp
     */
    private function fclose($fp, string $method): void
    {
        if ($method === 'bzip') {
            bzclose($fp);
        } elseif ($method === 'gzip') {
            gzclose($fp);
        } else {
            fflush($fp);
            fclose($fp);
        }
    }
}
