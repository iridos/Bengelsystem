<?php

class WizardStep {
    public string $page = "";
    public array $redirects = array();
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
                        if(isset($_GET[$key]) && $_GET[$key] == $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
                if(isset($redirect['!=']) && isset($redirect['id'])){
                    foreach($redirect['!='] as $key => $value){
                        if(isset($_GET[$key]) && $_GET[$key] != $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
                if(isset($redirect['>']) && isset($redirect['id'])){
                    foreach($redirect['>'] as $key => $value){
                        if(isset($_GET[$key]) && $_GET[$key] > $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
                if(isset($redirect['<']) && isset($redirect['id'])){
                    foreach($redirect['<'] as $key => $value){
                        if(isset($_GET[$key]) && $_GET[$key] < $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
                if(isset($redirect['>=']) && isset($redirect['id'])){
                    foreach($redirect['>='] as $key => $value){
                        if(isset($_GET[$key]) && $_GET[$key] >= $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
                if(isset($redirect['<=']) && isset($redirect['id'])){
                    foreach($redirect['<='] as $key => $value){
                        if(isset($_GET[$key]) && $_GET[$key] <= $value){
                            $_GET['step'] = $redirect['id'];
                        }
                    }
                }
            }
        }
    }
}

class Wizard {
    private string $header = "";
    private string $firststep = "";
    private array $steps = array();
    private string $footer = "";
    public function __construct($json_file = 'setupWizard.json'){
        $stepsArray = json_decode(file_get_contents($json_file), true);
        $this->header = $stepsArray['header'];
        $this->firststep = $stepsArray['firststep'];
        foreach($stepsArray['steps'] as $step){
            $this->steps[$step['id']] = new WizardStep($step);
        }
        $this->footer = $stepsArray['footer'];
    }
    public function renderPHP(){
        if(isset($_GET['stepfrom'])){
            $this->steps[$_GET['stepfrom']]->setCurrentStep();
        }
        echo $this->header;
        if(isset($_GET['step'])){
            echo $this->steps[$_GET['step']]->page;
        }
        else{
            echo $this->steps[$this->firststep]->page;
        }
        echo $this->footer;
    }
}
?>
