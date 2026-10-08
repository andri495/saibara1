<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterPayment;
use Illuminate\Http\Request;

class MasterPaymentWebController extends Controller
{
    public function index()
    {
        $data = MasterPayment::paginate(10);
        return view('master.payment.index', compact('data'));
    }

    public function create()
    {
        return view('master.payment.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe_pembayaran' => 'required|string|max:255',
        ]);

        MasterPayment::create($validated);

        return redirect()->route('master.payment.index')
            ->with('success', 'Data tipe pembayaran berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = MasterPayment::findOrFail($id);
        return view('master.payment.show', compact('data'));
    }

    public function edit($id)
    {
        $data = MasterPayment::findOrFail($id);
        return view('master.payment.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $data = MasterPayment::findOrFail($id);

        $validated = $request->validate([
            'tipe_pembayaran' => 'required|string|max:255',
        ]);

        $data->update($validated);

        return redirect()->route('master.payment.index')
            ->with('success', 'Data tipe pembayaran berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = MasterPayment::findOrFail($id);
        $data->delete();

        return redirect()->route('master.payment.index')
            ->with('success', 'Data tipe pembayaran berhasil dihapus');
    }
}
