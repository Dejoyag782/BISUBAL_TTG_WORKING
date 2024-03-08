<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use App\Services\Helpers;
use Illuminate\Http\Request;

use App\Events\PasswordResetRequested;

use App\Models\User;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['only' => ['showAccountPage', 'showActivationPage', 'updateAccount']]);
    }
    /**
     * Show page for logging user in
     */
    public function showLoginPage()
    {
        return view('auth.login');
    }

    /**
     * Log in a user
     *
     * @param Illuminate\Http\Request $request The HTTP request
     */
    public function loginUser(Request $request)
    {
        $rules = [
            'password' => 'required'
        ];

        $this->validate($request, $rules);

        $user = User::first();

        if (!$user) {
            return redirect()->back()->withErrors(['No user account has been set up yet']);
        }

        if (!Hash::check($request->password, $user->password)) {
            return redirect()->back()->withErrors(['Password is invalid']);
        }

        Auth::login($user);

        return redirect('/');
    }

    /**
     * Show account activation page where new user can set up his
     * account
     *
     * @return Illuminate\Http\Response Account activation view
     */
    public function showActivationPage()
    {
        $user = Auth::user();

        $department = DB::table('departments')->select('id','name')->get();

        
        return view('users.activate', compact('user','department'));
    }

    /**
     * Activate and set up account for user
     *
     * @param Illuminate\Http\Request $request The HTTP request
     * @return Illuminate\Http\Response Redirect to home page
     */
    public function activateUser(Request $request)
    {
        $user = Auth::user();

        if ($user->activated) {
            return redirect()->back()->withError('Your account is already activated');
        }

        $rules = [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required|confirmed',
        ];

        $messages = [
            'email.required' => 'Email is required for recovery purposes.'
        ];

        $this->validate($request, $rules, $messages);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'department'=> $request->department,
            'designation'=>$request->designation,
            'password' => bcrypt($request->password),
            'activated' => true
        ]);

        return redirect('/dashboard');
    }

    /**
     * Show the page to reuqest new password
     */
    public function showPasswordRequestPage()
    {
        $user = User::first();

        return view('users.password_request', compact('user'));
    }

    /**
     * Handle the request to reset password after user forgets
     * password
     *
     * @param Illuminate\Http\Request $request The HTTP request
     */
    public function requestPassword(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];

        $this->validate($request, $rules);

        $user = User::first();

        $token = Helpers::generateRandomString();

        DB::table('password_resets')->delete();
        DB::table('password_resets')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expiry_date' => Carbon::now()->addDay()->toDateTimeString()
        ]);

        event(new PasswordResetRequested($token, $request->email));

        return redirect('/reset_password');
    }

    /**
     * Show page for password reset
     *
     */
    public function showResetPassword()
    {
        return view('users.password_reset');
    }

    /**
     * Handle reset of password
     */
    public function resetPassword(Request $request)
    {
        $token = DB::table('password_resets')->first();

        $rules = [
            'token' => 'required'
        ];

        $this->validate($request, $rules);

        if (!$token || ($token && $token->token != $request->token)) {
            return redirect()->back()->withErrors(['Invalid token']);
        }

        if (Carbon::now()->gt(Carbon::parse($token->expiry_date))) {
            return redirect()->back()->withErrors(['Token has expired.Please request for new token']);
        }

        $user = User::first();
        $user->update([
            'activated' => false
        ]);

        Auth::login($user);
        return redirect('/');
    }

    /**
     * Show account settings page
     *
     */
    public function showAccountPage()
    {
        $user = Auth::user();

        return view('users.account', compact('user'));
    }

    /**
     * Update user account
     *
     * @param Illuminate\Http\Request $request The HTTP request
     */
    public function updateAccount(Request $request)
    {
        $rules = [
            'name' => 'required',
        ];

        if ($request->has('password') && $request->password) {
            $rules['password'] = 'confirmed';
            $rules['old_password'] = 'required';
        };

        $this->validate($request, $rules);

        $user = Auth::user();
        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($request->has('password') && $request->password) {
            if (!Hash::check($request->old_password, $user->password)) {
                return redirect()->back()->withErrors(['Current password is invalid']);
            }

            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return redirect()->back()->with('status', 'Your account has been updated');
    }
}
