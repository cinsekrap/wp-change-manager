<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCheckQuestionRequest;
use App\Http\Requests\Admin\UpdateCheckQuestionRequest;
use App\Models\CheckQuestion;

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
        CheckQuestion::create($request->validated());

        return redirect()->route('admin.questions.index')->with('success', 'Question created.');
    }

    public function edit(CheckQuestion $question)
    {
        return view('admin.questions.form', compact('question'));
    }

    public function update(UpdateCheckQuestionRequest $request, CheckQuestion $question)
    {
        $question->update($request->validated());

        return redirect()->route('admin.questions.index')->with('success', 'Question updated.');
    }

    public function destroy(CheckQuestion $question)
    {
        $question->delete();
        return redirect()->route('admin.questions.index')->with('success', 'Question deleted.');
    }
}
