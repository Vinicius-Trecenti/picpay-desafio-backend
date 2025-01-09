<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

use App\Models\Transaction;

class TransactionController extends Controller
{
    public function transfer(Request $request) {
        try {
            $validated = $request->validate([
                'value'     => 'required|numeric|min:0.01',
                'sender'    => 'required|exists:users,id',
                'receiver'  => 'required|exists:users,id',
            ]);

            $sender     = User::find($validated['sender']);
            $receiver   = User::find($validated['receiver']);
            $amout      = $validated['value'];

            // validando a regra de negociação

            if($sender->usertype !== 'common'){
                return response()->json(['error' => 'Apenas usuários comuns podem enviar dinheiro.'], 403);
            }

            if($sender->balance < $amout){
                return response()->json(['error' => 'Saldo insuficiente.'], 403);
            }

            if ($sender->id === $receiver->id) {
                return response()->json(['error' => 'Você não pode transferir dinheiro para si mesmo.'], 403);
            }

            // consultar serviço externo para transferência
            // dd($amout);
            $authResponse = Http::withOptions(['verify' => false])->get('https://util.devi.tools/api/v2/authorize');
            if ($authResponse->failed() || !$authResponse->json('data.authorization')) {
                return response()->json(['error' => 'Transferência não autorizada.'], 403);
            }

            DB::beginTransaction();
            try {
                $sender->balance -= $amout;
                $sender->save();

                $receiver->balance += $amout;
                $receiver->save();

                $transaction = new Transaction();
                $transaction->sender_id = $sender->id;
                $transaction->receiver_id = $receiver->id;
                $transaction->amount = $amout;
                $transaction->status = 'completed';
                $transaction->save();

                DB::commit();

                return response()->json(['message' => 'Transferência realizada com sucesso.'], 200);
            }
            catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['error' => $e->getMessage()], 500);
            }

        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
