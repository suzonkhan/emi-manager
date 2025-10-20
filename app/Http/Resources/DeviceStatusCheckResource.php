<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceStatusCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculate payment information
        $totalPaid = $this->installments()->where('status', 'paid')->sum('paid_amount');
        $totalPayable = $this->getTotalPayableAmount();
        $totalDue = max(0, $totalPayable - $totalPaid);
        
        // Get last payment and next due dates
        $lastPayment = $this->installments()
            ->where('status', 'paid')
            ->latest('paid_date')
            ->first();
        
        $nextDue = $this->installments()
            ->where('status', 'pending')
            ->orderBy('due_date', 'asc')
            ->first();

        // Generate lock message based on device status and payment info
        $lockMessage = $this->generateLockMessage($totalDue, $nextDue);

        return [
            'success' => true,
            'is_registered' => true,
            'device_id' => $this->id,
            'is_locked' => (bool) $this->is_device_locked,
            'lock_message' => $lockMessage,
            'customer' => [
                'id' => $this->id,
                'name' => $this->name,
                'mobile' => $this->mobile,
                'email' => $this->email,
                'nid_no' => $this->nid_no,
                'status' => $this->status,
            ],
            'product' => [
                'type' => $this->product_type,
                'model' => $this->product_model,
                'price' => (float) $this->product_price,
                'serial_number' => $this->serial_number,
                'imei_1' => $this->imei_1,
                'imei_2' => $this->imei_2,
            ],
            'payment_status' => [
                'total_payable' => (float) $totalPayable,
                'total_paid' => (float) $totalPaid,
                'total_due' => (float) $totalDue,
                'down_payment' => (float) $this->down_payment,
                'emi_per_month' => (float) $this->emi_per_month,
                'emi_duration_months' => $this->emi_duration_months,
                'last_payment_date' => $lastPayment?->paid_date?->format('Y-m-d'),
                'last_payment_amount' => $lastPayment ? (float) $lastPayment->paid_amount : null,
                'next_due_date' => $nextDue?->due_date?->format('Y-m-d'),
                'next_due_amount' => $nextDue ? (float) $nextDue->amount : null,
            ],
            'device_restrictions' => [
                'is_camera_disabled' => (bool) $this->is_camera_disabled,
                'is_bluetooth_disabled' => (bool) $this->is_bluetooth_disabled,
                'is_app_hidden' => (bool) $this->is_app_hidden,
                'is_call_disabled' => (bool) $this->is_call_disabled,
                'is_usb_locked' => (bool) $this->is_usb_locked,
                'has_password' => (bool) $this->has_password,
                'custom_wallpaper_url' => $this->custom_wallpaper_url,
            ],
            'dealer' => $this->dealer ? [
                'id' => $this->dealer->id,
                'name' => $this->dealer->name,
                'mobile' => $this->dealer->mobile,
                'email' => $this->dealer->email,
            ] : null,
            'token' => $this->token ? [
                'id' => $this->token->id,
                'token' => $this->token->token,
            ] : null,
        ];
    }

    /**
     * Generate appropriate lock message based on device and payment status
     */
    private function generateLockMessage(float $totalDue, $nextDue): string
    {
        if (!$this->is_device_locked) {
            return '';
        }

        $message = "⚠️ Device Locked\n\n";
        
        if ($totalDue > 0) {
            $message .= "Total Due: ৳" . number_format($totalDue, 2) . "\n";
            
            if ($nextDue) {
                $dueDate = $nextDue->due_date->format('d M Y');
                $message .= "Next Payment: ৳" . number_format($nextDue->amount, 2) . " by " . $dueDate . "\n";
            }
        }

        $message .= "\nPlease contact: " . ($this->mobile ?? 'your dealer');
        
        if ($this->dealer && $this->dealer->mobile) {
            $message .= "\nDealer: " . $this->dealer->mobile;
        }

        $message .= "\n\nPay your installment to unlock the device.";

        return $message;
    }
}

