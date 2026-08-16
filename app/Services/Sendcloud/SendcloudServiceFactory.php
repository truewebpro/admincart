<?php

namespace App\Services\Sendcloud;

use App\Models\Sendcloud;

class SendcloudServiceFactory
{
    public static function make(Sendcloud $sendcloud): SendcloudServiceInterface
    {
        return $sendcloud->api_version === 'v3'
            ? new SendcloudV3Service($sendcloud)
            : new SendcloudV2Service($sendcloud);
    }
}
