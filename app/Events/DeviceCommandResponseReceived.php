<?php

namespace App\Events;

use App\Models\DeviceCommandLog;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceCommandResponseReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DeviceCommandLog $commandLog;
    public array $responseData;

    /**
     * Create a new event instance.
     */
    public function __construct(DeviceCommandLog $commandLog, array $responseData = [])
    {
        $this->commandLog   = $commandLog;
        $this->responseData = $responseData;
    }
}

