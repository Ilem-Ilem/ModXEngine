<?php 


function normalizePath(string $path):string
    {
        return str_replace('/', '\\', $path);
    }