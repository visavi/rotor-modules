<?php

namespace Modules\Blog\Console;

use Illuminate\Console\Command;
use Modules\Blog\Models\Article;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class BlogActivation extends Command
{
    protected $signature = 'blog:activation';

    protected $description = 'Blog activation';

    public function handle(): int
    {
        Article::query()
            ->active(false)
            ->where('published_at', '<=', now())
            ->where('draft', false)
            ->each(function (Article $item) {
                $item->category->increment('count_articles');
                $item->update([
                    'active'     => true,
                    'created_at' => strtotime($item->published_at),
                ]);
            });

        $this->info('Blog successfully activated.');

        return SymfonyCommand::SUCCESS;
    }
}
