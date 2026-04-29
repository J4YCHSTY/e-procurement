<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HardwareRequest;
use App\Models\SoftwareRequest;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function storeHardware(Request $request) {
        $validatedData = $request->validate([
            'request_date' => 'required|date',
            'hardware_type' => 'required|string|max:255',
            'hardware_recommendation' => 'nullable|string|max:255',
            'justification' => 'required|string',
            'digital_signature' => 'required|boolean'
        ]);

        $hardwareRequest = new HardwareRequest();
        $hardwareRequest->user_id = Auth::id();
        $hardwareRequest->request_date = $validatedData['request_date'];
        $hardwareRequest->hardware_type = $validatedData['hardware_type'];
        $hardwareRequest->hardware_recommendation = $validatedData['hardware_recommendation'];
        $hardwareRequest->justification = $validatedData['justification'];
        $hardwareRequest->digital_signature = $validatedData['digital_signature'];
        $hardwareRequest->status = 'Pending';
        $hardwareRequest->save();

        return redirect()->back()->with('success', 'Form Request Hardware Berhasil Dikirim.');

    }

    public function storeSoftware(Request $request) {
        $validateData = $request->validate([
            'request_date' => 'required|date',
            'software_name' => 'required|string|max:255',
            'software_type' => 'nullable|string|max:255',
            'license_count' => 'required|integer|min:1',
            'duration_months' => 'required|integer|min:1',
            'estimated_cost' => 'required|numeric|min:0',
            'justification' => 'required|string',
            'digital_signature' => 'required|boolean'
        ]);

        $softwareRequest = new SoftwareRequest();
        $softwareRequest->user_id = Auth::id();
        $softwareRequest->request_date = $validateData['request_date'];
        $softwareRequest->software_name = $validateData['software_name'];
        $softwareRequest->software_type = $validateData['software_type'];
        $softwareRequest->license_count = $validateData['license_count'];
        $softwareRequest->duration_months = $validateData['duration_months'];
        $softwareRequest->estimated_cost = $validateData['estimated_cost'];
        $softwareRequest->justification = $validateData['justification'];
        $softwareRequest->digital_signature = $validateData['digital_signature'];
        $softwareRequest->status = 'Pending';
        $softwareRequest->save();

        return redirect()->back()->with('success', 'Form Request Software Berhasil Dikirim.');
    }
}
