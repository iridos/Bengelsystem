<?php

namespace Bengelsytem;

require_once 'WizardStep.php';

class Wizard
{
    private string $header = "";
    private string $firststep = "";
    private array $steps = array();
    private string $footer = "";
    private array $storedvariables = array();
    public function __construct($json_file = '../wizard.json')
    {
        $stepsArray = json_decode(file_get_contents($json_file), true);
        $this->header = $stepsArray['header'];
        $this->firststep = $stepsArray['firststep'];
        foreach ($stepsArray['steps'] as $step) {
            $this->steps[$step['id']] = new WizardStep($step);
        }
        $this->footer = $stepsArray['footer'];
        if (isset($_POST['storedvariables'])) {
            $this->storedvariables = json_decode($_POST['storedvariables'], JSON_FORCE_OBJECT);
        }
    }
    public function renderPHP()
    {
        echo $this->header;
        if (isset($_POST['stepfrom'])) {
            $this->steps[$_POST['stepfrom']]->setCurrentStep();
            if (isset($_POST['step']) && ($_POST['step'] != $_POST['stepfrom'])) {
                if (!is_null($this->steps[$_POST['stepfrom']]->code)) {
                    $this->storedvariables[$_POST['stepfrom']] = call_user_func($this->steps[$_POST['stepfrom']]->code, $this->storedvariables);
                }
            }
        }
        if (!isset($_POST['step'])) {
            $_POST['step'] = $this->firststep;
        }
        // If warning not set this does not add anything to the page:
        echo $this->steps[$_POST['step']]->warning;
        $pagedom = new DOMDocument();
        $pageval = $this->steps[$_POST['step']]->page;
        if (is_array($pageval)) {
            $page = "\xEF\xBB\xBF";
            foreach ($pageval as $pagepart) {
                foreach ($pagepart as $element => $content) {
                    if ($element == 'text') {
                        $page .= $content;
                    } elseif ($element == 'variable') {
                        $tmpcontent = $this->storedvariables;
                        foreach (explode('/', $content) as $pathpart) {
                            $tmpcontent = $tmpcontent[$pathpart];
                        }
                        $page .= $tmpcontent;
                    }
                }
            }
        } else {
            $page = "\xEF\xBB\xBF" . $pageval;
        }
        libxml_use_internal_errors(true);
        $pagedom->loadHTML($page);
        foreach (libxml_get_errors() as $error) {
            echo "<p>Error when parsing html for step \"" . $_POST['step'] . "\":" . $error->line . " " . $error->column . " " . $error->message . "</p>";
        }
        $forms = $pagedom->getElementsByTagName('form');
        foreach ($forms as $form) {
            $input = $pagedom->createElement('input');
            $input->setAttribute('type', 'hidden');
            $input->setAttribute('name', 'storedvariables');
            $input->setAttribute('value', json_encode($this->storedvariables, JSON_FORCE_OBJECT));
            $form->appendChild($input);
        }
        echo $pagedom->saveHTML();
        echo $this->footer;
    }
    public function addCode($stepid, $function)
    {
        $this->steps[$stepid]->setCode($function);
    }
}
