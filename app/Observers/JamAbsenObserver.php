<?php

namespace App\Observers;

class JamAbsenObserver
{
    public function saved() {
        cache()->flush();
    }
}
