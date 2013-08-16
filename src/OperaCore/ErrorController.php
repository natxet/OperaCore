<?php

namespace OperaCore;

interface ErrorController
{
    public function action403();
    public function action404();
    public function action500( $message );
}
