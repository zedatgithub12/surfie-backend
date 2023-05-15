<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetLink;
use Illuminate\Http\Request;
use App\Models\Partners;
use App\Models\Customers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $user = Partners::where('email', $credentials['email'])->first();
        if ($user && Hash::check($credentials['password'], $user->password)) {
            // The email and password match a record in the database
            $message = $user;
        } else {
            // No record was found with the given email and password
            $message = "83";
        }
        return response()->json($message, 200);
    }

    /**
     * Register Partner 
     * it will be added to partners table
     */
    public function register(Request $request)
    {
        $referralCode = "";
        do {
            $referralCode = "surfie" . Str::random(6); // Generate a random value
        } while (Partners::where('referralcode', $referralCode)->exists());
        $emails = Partners::where('email', $request->email)->exists();
        $phones = Partners::where('phone', $request->phone)->exists();
        $organization = Partners::where('organization', $request->organization)->exists();

        if ($emails) {
            $message = "80";
        } else if ($phones) {
            $message = "81";
        } else if (!$request->organization == "" && $organization) {
            $message = "82";
        } else {

            Partners::create([
                "fname" => $request->fname,
                "mname" => $request->mname,
                "lname" => $request->lname,
                "email" => $request->email,
                "phone" => $request->phone,
                "organization" => $request->organization,
                "balance" => 0,
                "noreferral" => 0,
                "password" => bcrypt($request->password),
                "referralcode" => $referralCode,
                "status" => 0,
            ]);
            $message = "200";
        }

        return response()->json($message, 200);
    }
    public function forgotpassword(Request $request)
    {
        $email = $request->email;
        $user = Partners::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email not found'], 404);
        }
        $token = Str::random(60);
        $addtotable = DB::table('password_reset_tokens')->where('email', $email)->first();
        if (!$addtotable) {
            DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => $token,
                'created_at' => now()
            ]);
            $message = "Reset your password";
            $data = ([
                'token' => $token,
                'message' => $message,

            ]);
            Mail::to($email)->send(new PasswordResetLink($data));

            return response()->json(['message' => 'Password reset link sent to your email'], 200);
        } else {
            return response()->json(['message' => 'We have already sent you reset link check your inbox or spam folder'], 404);
        }

    }

    public function resetpassword(Request $request)
    {
        $token = $request->token;
        $password = $request->password;
        $reset = DB::table('password_reset_tokens')->where('token', $token)->first();
        if (!$reset) {
            return response()->json(['message' => 'Invalid token'], 404);
        }
        $user = Partners::where('email', $reset->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        Partners::where('email', $user->email)->update(['password' => Hash::make($password)]);
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json(['message' => 'Password reset successful'], 200);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customers = Customers::query()->where('referralcode', $id)->orderByDesc('id')->paginate(12);
        return response()->json($customers, 200);
    }

    /**
     * fetch customers in storage.
     */
    public function index()
    {
        $partners = Partners::all();
        return response()->json([
            'success' => true,
            'data' => $partners
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $partner = Partners::find($id);

        if (!$partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        $validatedData = $request->validate([
            'fname' => 'required|string|max:255',
            'mname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'organization' => 'nullable|string|max:255',

        ]);

        $partner->fname = $validatedData['fname'];
        $partner->mname = $validatedData['mname'];
        $partner->lname = $validatedData['lname'];
        $partner->phone = $validatedData['phone'];
        $partner->organization = $validatedData['organization'];

        if ($partner->save()) {
            $updatedPartner = Partners::find($id);
            // return the updated partner object in the response

            return response()->json(['message' => 'updated successfully', 'profile' => $updatedPartner], 200);
        } else {
            return response()->json(['message' => 'Unable to update partner'], 500);
        }

    }


    public function changepass(Request $request, string $id)
    {
        $user = Partners::find($id);
        if (!$user) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        $this->validate($request, [
            'oldpass' => 'required',
            'newpass' => 'required|string',
        ]);

        $hashedPassword = $user->password;

        if (Hash::check($request->oldpass, $hashedPassword)) {

            $user->fill([
                'password' => Hash::make($request->newpass)
            ])->save();
            return response()->json(['message' => 'changed successfully'], 200);

        } else {
            return response()->json(['message' => 'password does not match'], 500);
        }

    }
    /**
     * Remove the specified resource from storage.
     */
    public function balance(Request $request, string $id)
    {

        $customers = Customers::whereMonth('created_at', '=', date('m'))->where('referralcode', '=', $request->referral)->count();
        $total = Customers::where('referralcode', $request->referral)->count();
        $profile = Partners::find($id);

        return response()->json(['total' => $total, 'monthly' => $customers, 'balance' => $profile->noreferral], 200);


    }

    public function destroy(string $id)
    {
        $record = Partners::find($id);
        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Record not found'], 404);
        }
        $record->delete();
        return response()->json([
            'success' => true,
            'message' => 'Partner info deleted successfully.'
        ], 200);
    }
}