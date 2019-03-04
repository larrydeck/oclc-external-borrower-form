<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Kris\LaravelFormBuilder\FormBuilder;

class BorrowerController extends BaseController {

    public function create(FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(\App\Forms\BorrowerForm::class, [
            'method' => 'POST',
            'url' => route('borrower.store')
        ]);

        return view('borrower.create', compact('form'));
    }

    public function store(FormBuilder $formBuilder)
    {
        $form = $formBuilder->create(\App\Forms\BorrowerForm::class);

        if (!$form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        // Do saving and other things...a
 	// Or automatically redirect on error. This will throw an HttpResponseException with redirect
        $form->redirectIfNotValid();

        //Borrower::create($form->getFieldValues());
        //return redirect()->route('borrower');

    }
}