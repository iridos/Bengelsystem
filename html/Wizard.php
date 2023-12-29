<?php

class WizardStep {
    public string $page = "";
    public array $redirects = array();
    public string $warning = "";
    public $code = null;
    public function __construct($step){
        if(isset($step['page'])){
            $this->page = $step['page'];
        }
        if(isset($step['redirects'])){
            $this->redirects = $step['redirects'];
        }
    }
    public function setCurrentStep(){
        if(!empty($this->redirects)){
            foreach($this->redirects as $redirect){
                if(isset($redirect['==']) && isset($redirect['id'])){
                    foreach($redirect['=='] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] == $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if(isset($redirect['!=']) && isset($redirect['id'])){
                    foreach($redirect['!='] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] != $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if(isset($redirect['>']) && isset($redirect['id'])){
                    foreach($redirect['>'] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] > $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if(isset($redirect['<']) && isset($redirect['id'])){
                    foreach($redirect['<'] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] < $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if(isset($redirect['>=']) && isset($redirect['id'])){
                    foreach($redirect['>='] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] >= $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if(isset($redirect['<=']) && isset($redirect['id'])){
                    foreach($redirect['<='] as $key => $value){
                        if(isset($_POST[$key]) && $_POST[$key] <= $value){
                            $_POST['step'] = $redirect['id'];
                            if(isset($redirect['warning'])){
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
            }
        }
    }
    public function setCode(callable $function){
        $this->code = $function;
    }
}

class Wizard {
    private string $header = "";
    private string $firststep = "";
    private array $steps = array();
    private string $footer = "";
    private array $storedvariables = array();
    public function __construct($json_file = 'setupWizard.json'){
        $stepsArray = json_decode(file_get_contents($json_file), true);
        $this->header = $stepsArray['header'];
        $this->firststep = $stepsArray['firststep'];
        foreach($stepsArray['steps'] as $step){
            $this->steps[$step['id']] = new WizardStep($step);
        }
        $this->footer = $stepsArray['footer'];
        if(isset($_POST['storedvariables'])){
            $this->storedvariables = json_decode($_POST['storedvariables'],JSON_FORCE_OBJECT);
        }
    }
    public function renderPHP(){
        echo $this->header;
        if(isset($_POST['stepfrom'])){
            $this->steps[$_POST['stepfrom']]->setCurrentStep();
            if(isset($_POST['step']) && ($_POST['step'] != $_POST['stepfrom'])){
                if(!is_null($this->steps[$_POST['stepfrom']]->code)){
                    $this->storedvariables[$_POST['stepfrom']] = call_user_func($this->steps[$_POST['stepfrom']]->code, $this->storedvariables);
                }
            }
        }
        if(!isset($_POST['step'])){
            $_POST['step'] = $this->firststep;
        }
        // If warning not set this does not add anything to the page:
        echo $this->steps[$_POST['step']]->warning;
        $pagedom = new DOMDocument();
        $pagedom->loadHTML("\xEF\xBB\xBF".$this->steps[$_POST['step']]->page);
        $forms = $pagedom->getElementsByTagName('form');
        foreach($forms as $form){
            $input = $pagedom->createElement('input');
            $input->setAttribute('type','hidden');
            $input->setAttribute('name','storedvariables');
            $input->setAttribute('value',json_encode($this->storedvariables,JSON_FORCE_OBJECT));
            $form->appendChild($input);
        }
        echo $pagedom->saveHTML();
        echo $this->footer;
    }
    public function addCode($stepid, $function){
        $this->steps[$stepid]->setCode($function);
    }
}
?>
