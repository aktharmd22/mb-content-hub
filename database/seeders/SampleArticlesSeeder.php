<?php

namespace Database\Seeders;

use App\Enums\ArticleStage;
use App\Models\Article;
use App\Models\Client;
use App\Models\StageHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleArticlesSeeder extends Seeder
{
    /**
     * Creates sample clients + articles WITHOUT touching Google Drive,
     * so it's safe to run before Drive is configured.
     */
    public function run(): void
    {
        $admin   = User::where('username', 'admin')->first();
        $sales1  = User::where('username', 'sales1')->first();
        $sales2  = User::where('username', 'sales2')->first();
        $writer1 = User::where('username', 'writer1')->first();
        $writer2 = User::where('username', 'writer2')->first();
        $lead1   = User::where('username', 'lead1')->first();

        if (! $sales1 || ! $writer1 || ! $lead1) {
            $this->command?->warn('SampleArticlesSeeder: prerequisite users missing. Run SampleUsersSeeder first.');
            return;
        }

        $clients = [
            ['name' => 'Acme Corp',         'company' => 'Acme Corporation Sdn Bhd', 'contact_email' => 'hello@acme.test',     'created_by' => $sales1->id],
            ['name' => 'Pacific Holdings',  'company' => 'Pacific Holdings Bhd',     'contact_email' => 'press@pacific.test',  'created_by' => $sales1->id],
            ['name' => 'GreenTech',         'company' => 'GreenTech Innovations',    'contact_email' => 'pr@greentech.test',   'created_by' => $sales2->id],
        ];

        $clientModels = collect($clients)->map(fn ($c) => Client::firstOrCreate(['name' => $c['name']], $c));

        $samples = [
            [
                'title'        => 'How AI is reshaping Malaysian fintech',
                'client'       => $clientModels[0],
                'sales_rep'    => $sales1,
                'tech_writer'  => $writer1,
                'tech_lead'    => null,
                'current_stage' => ArticleStage::IN_PROGRESS,
                'priority'     => 'high',
                'deadline'     => now()->addDays(3),
                'word_count'   => 1200,
                'history'      => [ArticleStage::INBOX, ArticleStage::ASSIGNED, ArticleStage::IN_PROGRESS],
            ],
            [
                'title'        => 'Pacific Holdings Q1 results profile',
                'client'       => $clientModels[1],
                'sales_rep'    => $sales1,
                'tech_writer'  => $writer2,
                'tech_lead'    => $lead1,
                'current_stage' => ArticleStage::INTERNAL_REVIEW,
                'priority'     => 'medium',
                'deadline'     => now()->addDays(5),
                'word_count'   => 800,
                'history'      => [ArticleStage::INBOX, ArticleStage::ASSIGNED, ArticleStage::IN_PROGRESS, ArticleStage::INTERNAL_REVIEW],
            ],
            [
                'title'        => 'Sustainable manufacturing case study',
                'client'       => $clientModels[2],
                'sales_rep'    => $sales2,
                'tech_writer'  => null,
                'tech_lead'    => null,
                'current_stage' => ArticleStage::INBOX,
                'priority'     => 'low',
                'deadline'     => now()->addDays(10),
                'word_count'   => 1500,
                'history'      => [ArticleStage::INBOX],
            ],
            [
                'title'        => 'GreenTech CEO interview',
                'client'       => $clientModels[2],
                'sales_rep'    => $sales2,
                'tech_writer'  => $writer1,
                'tech_lead'    => $lead1,
                'current_stage' => ArticleStage::CLIENT_APPROVAL,
                'priority'     => 'high',
                'deadline'     => now()->addDays(2),
                'word_count'   => 1000,
                'history'      => [ArticleStage::INBOX, ArticleStage::ASSIGNED, ArticleStage::IN_PROGRESS, ArticleStage::INTERNAL_REVIEW, ArticleStage::CLIENT_APPROVAL],
            ],
        ];

        $code = (Article::max('id') ?? 0) + 1;

        foreach ($samples as $s) {
            $article = Article::firstOrCreate(
                ['title' => $s['title']],
                [
                    'article_code'      => 'ART-' . str_pad((string) $code++, 3, '0', STR_PAD_LEFT),
                    'client_id'         => $s['client']->id,
                    'sales_rep_id'      => $s['sales_rep']->id,
                    'tech_writer_id'    => $s['tech_writer']?->id,
                    'tech_lead_id'      => $s['tech_lead']?->id,
                    'current_stage'     => $s['current_stage']->value,
                    'priority'          => $s['priority'],
                    'deadline'          => $s['deadline'],
                    'word_count_target' => $s['word_count'],
                    'submitted_at'      => now()->subDays(5),
                    'stage_entered_at'  => now()->subDay(),
                ]
            );

            if ($article->wasRecentlyCreated) {
                $previous = null;
                $when     = now()->subDays(5);
                foreach ($s['history'] as $stage) {
                    StageHistory::create([
                        'article_id' => $article->id,
                        'from_stage' => $previous?->value,
                        'to_stage'   => $stage->value,
                        'changed_by' => $admin?->id,
                        'changed_at' => $when,
                        'notes'      => "Seeded transition to {$stage->label()}",
                    ]);
                    $previous = $stage;
                    $when     = $when->copy()->addDay();
                }
            }
        }

        $this->command?->info('Sample articles seeded.');
    }
}
