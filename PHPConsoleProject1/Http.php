<?php

use GuzzleHttp\Client;

class Http
{
    private $settings;
    private $appKey;
    private $client;

    const EXCEPTION_NAME = "exception";
    const RESULT_NAME = "result";

    public function __construct($settings, $appKey)
    {
        $this->settings = $settings;

        $this->appKey = $appKey;

        $this->client = new Client();
    }

    public function getAccessTokenString()
    {
        if ($this->settings->getAccessTokenString() == null)
        {
            $this->registerDevice();
        }

        return $this->settings->getAccessTokenString();
    }

    private function registerDevice()
    {
        $response = $this->handleDictionaryRequests('POST', "/devices", [
            "appId" =>  $this->settings->getAppId(),
            "appKey" =>  $this->appKey,
            "platform" =>  PHP_OS,
            "model" =>  "",
            "osVersion" =>  "",
            "uniqueId" =>  $this->settings->getUniqueId(),
        ]);

        if (!in_array(self::EXCEPTION_NAME, $response))
        {
            $this->settings->setDeviceToken($response[self::RESULT_NAME]);
        }
    }

    private function handleDictionaryRequests($verb, $path, $dictionary, $file = null)
    {
        $dictionary = ['json' => $dictionary];

        if ($file != null)
        {
            $dictionary['multipart'] = [['data' => ["data" => $file]]];
        }

        return $this->handleRequest($verb, $path, $dictionary);
    }

    private function handleParametersRequests($verb, $path, $parameters)
    {
        $dictionary = [['params' => $parameters]];

        return $this->handleRequest($verb, $path, $dictionary);
    }

    private function handleRequest($verb, $path, $dictionary)
    {
        $this->handleLastLocation($dictionary);

        $url = $this->getUrl($path);

        $response = null;

        try
        {
            # TODO: turn on SSL validation
            $dictionary['verify'] = false;

            $response = $this->client->$verb($url, $dictionary);
        }
        catch (Exception $ex)
        {
            $response = [EXCEPTION_NAME => $ex];
        }

        return $response == null ? [EXCEPTION_NAME => new GuzzleHttp\Exception\TransferException()] : $response->json();
    }

    private function handleLastLocation($dictionary)
    {
        return $dictionary;
    }

    private function getUrl($path)
    {
        $url = $this->settings->getServiceRoot();

        $url->setPath($path);

        return $url;
    }

    public function get($path, $parameters)
    {
        return $this->handleParametersRequests('GET', $path, $parameters);
    }

    public function delete($path, $parameters)
    {
        return $this->handleParametersRequests('DELETE', $path, $parameters);
    }

    public function patch($path, $parameters)
    {
        return $this->handleDictionaryRequests('PATCH', $path, $parameters);
    }

    public function post($path, $parameters)
    {
        return $this->handleDictionaryRequests('POST', $path, $parameters);
    }

    public function put($path, $parameters)
    {
        return $this->handleDictionaryRequests('PUT', $path, $parameters);
    }

    public function createUser($userName, $password, $firstName=null, $lastName=null, $email=null, $gender=null, $dateOfBirth=null, $tag=null)
    {
        $response = $this->post("/users", [
            "username" => $userName,
            "password" => $password,
            "firstName" => $firstName,
            "lastName" => $lastName,
            "email" => $email,
            "gender" => $gender,
            "dateOfBirth"=> $dateOfBirth,
            "tag" =>$tag
        ]);

        if (!in_array(self::EXCEPTION_NAME, $response))
        {
            $this->settings->setUser($response[self::RESULT_NAME]);
        }

        return $response;
    }

    public function loginUser($userName, $password)
    {
        $response = $this->post("/users/login", [
            "username" => $userName,
            "password" => $password
        ]);

        if (!in_array(self::EXCEPTION_NAME, $response))
        {
            $this->settings->setUser($response[self::RESULT_NAME]);
        }

        return $response;
    }

    public function logoutUser()
    {
        $this->settings->setUser(null);
    }
}