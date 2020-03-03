<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mail;

class UsersController extends Controller
{
	public function __construct()
	{
		$this->middleware('auth',[
			'except' => ['show', 'create', 'store', 'index','confirmEmail']
		]);

		$this->middleware('guest', [
			'only' => ['create']
		]);
	}

	public function index()
	{
		$users = User::paginate(10);
		return view('users.index', compact('users'));
	}
	
	public function create()
	{
		return view('users.create');
    }

	/**
	 * 显示信息
	 * @param User $user
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function show(User $user)
	{
		return view('users.show', compact('user'));
    }

	public function store(Request $request)
	{
		$this->validate($request, [
			'name' => 'required|unique:users|max:50',
			'email' => 'required|email|unique:users|max:255',
			'password' => 'required|confirmed|min:6'
		]);

		$user = User::create([
			'name' => $request->name,
			'email' => $request->email,
			'password' => bcrypt($request->password),
		]);

		$this->sendEmailConfirmationTo($user);
		session()->flash('success', '验证邮件已经发送到您的注册邮箱上，请注意查收!');
		return redirect('/');
//		Auth::login($user);
//		session()->flash('success', '欢迎，您将在这里开启一段新的旅程~');
//		return redirect()->route('users.show', [$user]);
    }

	public function edit(User $user)
	{
		$this->authorize('update', $user);
		return view('users.edit', compact('user'));
    }

	public function update(User $user,Request $request)
	{
		$this->authorize('update', $user);
		$this->validate($request, [
			'name' => 'required|max:50',
			'password' => 'nullable|confirmed|min:6'
		]);

		$data['name'] =$request->name;
		if ($request->password){
			$data['password'] = bcrypt($request->password);
		}

		$user->update($data);

		session()->flash('success', '个人资料更新成功');
		return redirect()->route('users.show', $user);
    }

	/**
	 * 删除用户
	 * @param User $user
	 * @return \Illuminate\Http\RedirectResponse
	 * @throws \Illuminate\Auth\Access\AuthorizationException
	 */
	public function destroy(User $user)
	{
		$this->authorize('destory', $user);
		$user->delete();
		session()->flash('success', '删除用户成功');
		return back();
    }

	/**
	 * 发送激活邮件
	 * @param $user
	 */
	public function sendEmailConfirmationTo($user)
	{
		$view = 'emails.confirm';
		$data = compact('user');
		$from = 'summer@example.com';
		$name = 'Summer';
		$to = $user->email;
		$subject = '感谢注册 WeiBo 应用！请确认您的邮箱!';

		Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
			$message->from($from, $name)->to($to)->subject($subject);
		});
    }

	/**
	 * 邮箱激活
	 * @param $token
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function confirmEmail($token)
	{
		/**@var User $user*/
		$user = User::where('activation_token', $token)->firstOrFail();

		$user->activated = true;
		$user->activation_token = null;
		$user->save();

		Auth::login($user);

		session()->flash('success', '恭喜您！激活成功！');

		return redirect()->route('users.show', [$user]);
    }
}
