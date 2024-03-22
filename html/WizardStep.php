<?php

namespace Bengelsystem;

class WizardStep
{
    public $page = "";
    public array $redirects = array();
    public string $warning = "";
    public $code = null;
    public function __construct($step)
    {
        if (isset($step['page'])) {
            $this->page = $step['page'];
        }
        if (isset($step['redirects'])) {
            $this->redirects = $step['redirects'];
        }
    }
    public function setCurrentStep()
    {
        if (!empty($this->redirects)) {
            foreach ($this->redirects as $redirect) {
                if (isset($redirect['==']) && isset($redirect['id'])) {
                    foreach ($redirect['=='] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] == $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if (isset($redirect['!=']) && isset($redirect['id'])) {
                    foreach ($redirect['!='] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] != $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if (isset($redirect['>']) && isset($redirect['id'])) {
                    foreach ($redirect['>'] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] > $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if (isset($redirect['<']) && isset($redirect['id'])) {
                    foreach ($redirect['<'] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] < $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if (isset($redirect['>=']) && isset($redirect['id'])) {
                    foreach ($redirect['>='] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] >= $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
                if (isset($redirect['<=']) && isset($redirect['id'])) {
                    foreach ($redirect['<='] as $key => $value) {
                        if (isset($_POST[$key]) && $_POST[$key] <= $value) {
                            $_POST['step'] = $redirect['id'];
                            if (isset($redirect['warning'])) {
                                $this->warning = $redirect['warning'];
                            }
                            return;
                        }
                    }
                }
            }
        }
    }
    public function setCode(callable $function)
    {
        $this->code = $function;
    }
}
