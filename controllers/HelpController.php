<?php

/**
 * .

 * Date: 15/1/12
 * Time: 09:39
 */
class HelpController extends TController
{

    public function filters()
    {
        return null;
    }

    public function init()
    {
        parent::init();
        $this->layout = 'help';
    }

    public function actionAbout()
    {
        $this->render('about');
    }

    public function actionFaqs()
    {
        $this->render('faqs');
    }

    public function actionContact()
    {
        $this->render('contact');
    }

    public function actionPrivacy()
    {
        $this->render('privacy');
    }

    public function actionResponsibility()
    {
        $this->render('responsibility');
    }

    public function actionTerms()
    {
        $this->render('terms');
    }
} 