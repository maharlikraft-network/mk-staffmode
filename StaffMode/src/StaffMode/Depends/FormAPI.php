<?php

declare(strict_types = 1);

namespace StaffMode\Depends;

use StaffMode\Depends\ModalForm;
use StaffMode\Depends\Form;
use StaffMode\Depends\CustomForm;
use StaffMode\Depends\SimpleForm;

trait FormAPI {

    public function createCustomForm(callable $function = null) : CustomForm {
        return new CustomForm($function);
    }

    public function createSimpleForm(callable $function = null) : SimpleForm {
        return new SimpleForm($function);
    }
    
    public function createModalForm(callable $function = null) : ModalForm {
        return new ModalForm($function);
    }
}