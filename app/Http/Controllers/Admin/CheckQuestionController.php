<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCheckQuestionRequest;
use App\Http\Requests\Admin\UpdateCheckQuestionRequest;
use App\Models\CheckQuestion;
use App\Services\AuditService;

class CheckQuestionController extends Controller
{
    public function index()
    {
        $questions = CheckQuestion::ordered()->paginate(25);
        return view('admin.questions.index', compact('questions'));
    }

    public function create()
    {
        return view('admin.questions.form', ['question' => new CheckQuestion(['options' => ['Yes', 'No']])]);
    }

    public function store(StoreCheckQuestionRequest $request)
    {
        $question = CheckQuestion::create($request->validated());

        AuditService::log(
            action: 'created',
            model: $question,
            description: "Created check question: {$question->question_text}",
            newValues: ['question_text' => $question->question_text],
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function edit(CheckQuestion $question)
    {
        return view('admin.questions.form', compact('question'));
    }

    public function update(UpdateCheckQuestionRequest $request, CheckQuestion $question)
    {
        $oldValues = $question->only(['question_text', 'sort_order', 'is_active']);
        $question->update($request->validated());
        $newValues = $question->only(['question_text', 'sort_order', 'is_active']);

        AuditService::log(
            action: 'updated',
            model: $question,
            description: "Updated check question: {$question->question_text}",
            oldValues: $oldValues,
            newValues: $newValues,
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function destroy(CheckQuestion $question)
    {
        $questionText = $question->question_text;
        $question->delete();

        AuditService::log(
            action: 'deleted',
            model: $question,
            description: "Deleted check question: {$questionText}",
            oldValues: ['question_text' => $questionText],
        );

        return redirect()->route('admin.questions.index')->with('success', 'Question deleted.');
    }
}
