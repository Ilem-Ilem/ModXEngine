<?php

namespace ModXengine\Environment;

interface Environment{

    public function checkPath(string $path):bool;

    public function addPath(string $path):Environment;

    public function getPaths():array;
    // public function createPath():void;
}