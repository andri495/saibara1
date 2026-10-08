<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterPaymentStatus;
use Illuminate\Http\Request;

class MasterPaymentStatusController extends Controller
{
    public function index()
    {
        $data = MasterPaymentStatus::paginate(10);
        return view('master.payment-status.index', compact('data'));
    }

    public function create()
    {
        return view('master.payment-status.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'status_pembayaran' => 'required|string|max:255',
        ]);

        MasterPaymentStatus::create($validated);

        return redirect()->route('master.payment-status.index')
            ->with('success', 'Data status pembayaran berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterPaymentStatus::findOrFail($id);
        return view('master.payment-status.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterPaymentStatus::findOrFail($id);
        return view('master.payment-status.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterPaymentStatus::findOrFail($id);

        $validated = $request->validate([
            'status_pembayaran' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.payment-status.index')
            ->with('success', 'Data status pembayaran berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterPaymentStatus::findOrFail($id);
        $data->delete();

        return redirect()->route('master.payment-status.index')
            ->with('success', 'Data status pembayaran berhasil dihapus');
    }
}
