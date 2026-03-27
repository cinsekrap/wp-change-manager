<?php

namespace Database\Seeders;

use App\Models\CheckQuestion;
use Illuminate\Database\Seeder;

class CheckQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'question_text' => 'Has this content been approved by the relevant service lead or manager?',
                'options' => [
                    ['label' => 'Yes', 'pass' => true],
                    ['label' => 'No', 'pass' => false],
                    ['label' => 'Not applicable', 'pass' => true],
                ],
                'sort_order' => 0,
                'is_required' => true,
            ],
            [
                'question_text' => 'Have you checked the content for accuracy and spelling?',
                'options' => [
                    ['label' => 'Yes', 'pass' => true],
                    ['label' => 'No', 'pass' => false],
                ],
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                'question_text' => 'Does this change have a specific go-live date?',
                'options' => [
                    ['label' => 'Yes', 'pass' => true],
                    ['label' => 'No', 'pass' => true],
                ],
                'sort_order' => 2,
                'is_required' => false,
            ],
        ];

        foreach ($questions as $question) {
            CheckQuestion::updateOrCreate(
                ['question_text' => $question['question_text']],
                $question
            );
        }
    }
}
