<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Pegawai;

class JwtGuard implements Guard
{
    protected $request;
    protected $user;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getToken()
    {
        $header = $this->request->header('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) return null;

        return substr($header, 7);
    }

    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $token = $this->getToken();
        if (!$token) return null;

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $this->user = Pegawai::find($decoded->sub);
        } catch (\Exception $e) {
            return null;
        }

        return $this->user;
    }

    public function check()
    {
        return $this->user() !== null;
    }

    public function guest()
    {
        return !$this->check();
    }

    public function id()
    {
        return $this->user()?->id;
    }

    public function validate(array $credentials = [])
    {
        return false; // login tidak pakai validate()
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function hasUser()
    {
        return $this->user !== null;
    }
}
