<?php

namespace App\Http\Livewire\Users\CustomersAccounts;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomersAccounts extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Users = [];
    public $Roles = [];
    public $UserRolesName = [];
    public $UserRoles = [];
    public $User;
    public $UserId, $name, $email, $password, $ConfirmPassword, $status, $plan;
    public $SearchName, $SearchEmail, $SearchRole, $SearchStatus;
    public $RoleSelect, $StatusSelect;

    protected $listeners = [
        'SelectAdministratorRoles',
        'SelectAdministratorStatus',
    ];

    public function hydrate()
    {
        $this->emit('select2');
    }

    public function mount()
    {
        //
    }

    public function render()
    {
        $this->Roles = Role::all();

        $SearchName = $this->SearchName.'%';
        $SearchEmail = $this->SearchEmail.'%';

        if($this->RoleSelect != NULL)
        {
            $AdministratorsByRole = DB::table('model_has_roles')->where('role_id',$this->RoleSelect)->pluck('model_id');
        }else{
            $AdministratorsByRole = User::pluck('id');
        }

        if($this->StatusSelect != NULL)
        {
            $AdministratorsByStatus = User::where('status', $this->StatusSelect)->pluck('id');
        }else{
            $AdministratorsByStatus = User::pluck('id');
        }

        // استخدام Customer للعملاء
        $Users = User::where('plan', 'Customer')
                ->where('name','LIKE', $SearchName)
                ->where('email','LIKE', $SearchEmail)
                ->whereIn('id', $AdministratorsByRole)
                ->whereIn('id', $AdministratorsByStatus)
                ->orderBy('id','ASC')
                ->paginate(10);

        $links = $Users;
        $this->Users = collect($Users->items());

        return view('livewire.users.customers-accounts.customers-accounts', [
            'links' => $links
        ]);
    }

    public function SelectAdministratorRoles($AdministratorRolesId)
    {
        if ($AdministratorRolesId) {
            $this->RoleSelect = $AdministratorRolesId;
        }
        else{
            $this->reset('RoleSelect');
        }
    }

    public function SelectAdministratorStatus($AdministratorStatus)
    {
        if ($AdministratorStatus) {
            $this->StatusSelect = $AdministratorStatus;
        }
        else{
            $this->reset('StatusSelect');
        }
    }

    public function GetUsersAccount($UserId)
    {
        $this->resetValidation();
        $this->reset('name', 'email', 'plan', 'status', 'UserRolesName');

        $this->User = User::find($UserId);
        $this->UserId = $this->User->id;
        $this->UserRolesName = $this->User->getRoleNames()->toArray();
        $this->UserRoles = $this->User->roles->pluck('id')->toArray();

        $this->name = $this->User->name;
        $this->email = $this->User->email;
        $this->status = $this->User->status;
        $this->plan = $this->User->plan;
    }

    public function UsersAccountAdd()
    {
        $this->resetValidation();
        $this->reset('name', 'email', 'password', 'status', 'plan', 'UserRoles');
        $this->mount();
    }

    public function store()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|min:3|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:ConfirmPassword',
            'UserRoles' => 'required',
            'plan' => 'required',
            'status' => 'required',
        ], [
            'name.required' => 'حقل الاسم مطلوب',
            'name.min' => 'يجب ألا يقل الاسم عن 3 أحرف',
            'name.unique' => 'الاسم تم استخدامه',
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.email' => 'يجب كتابة بريد الكتروني صحيح',
            'email.unique' => 'البريد الإلكتروني تم استخدامه',
            'password.required' => 'حقل كلمة المرور مطلوب',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف',
            'password.same' => 'يجب أن تتطابق كلمة المرور مع تأكيد كلمة المرور',
            'UserRoles.required' => 'حقل دور العميل مطلوب',
            'plan.required' => 'خطة عمل العميل مطلوب',
            'status.required' => 'حقل حالة العميل مطلوب',
        ]);

        $User = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'plan' => $this->plan,
            'status' => $this->status,
        ]);

        $User->assignRole($this->UserRoles);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('success', [
            'message' => 'تم اضافة حساب العميل بنجاح',
            'title' => 'اضافة حساب'
        ]);
    }

    public function update()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|unique:users,name,'.$this->UserId,
            'email' => 'required|email|unique:users,email,'.$this->UserId,
            'password' => 'nullable|min:8|same:ConfirmPassword',
            'UserRoles' => 'required',
            'plan' => 'required',
            'status' => 'required',
        ],
        [
            'name.required' => 'أسم العميل مطلوب.',
            'name.unique' => 'تم أخذ هذا الأسم بالفعل',
            'email.required' => 'البريد الالكتروني مطلوب.',
            'email.email' => 'يجب التأكد من البريد الالكتروني.',
            'email.unique' => 'البريد الالكتروني مسجل سابقاً',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف',
            'password.same' => 'يجب أن تتطابق كلمة المرور مع تأكيد كلمة المرور',
            'UserRoles.required' => 'دور العميل مطلوب.',
            'plan.required' => 'يجب تحديد خطة العميل.',
            'status.required' => 'يجب تحديد حالة العميل.',
        ]);

        $User = User::find($this->UserId);

        if(!empty($this->password)){
            $this->password =  Hash::make($this->password);
            } else {
                $this->password = $User->password;
        }

        $User->update([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'plan' => $this->plan,
            'status' => $this->status,
        ]);

        DB::table('model_has_roles')->where('model_id',$this->UserId)->delete();
        $User->assignRole($this->UserRoles);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('success', [
            'message' => 'تم تعديل حساب العميل بنجاح',
            'title' => 'تعديل الحساب'
        ]);
    }

    public function destroy()
    {
        $User = User::find($this->UserId);
        if(!$User->status)
        {
            $User->delete();

            $this->reset();
            $this->mount();
            $this->dispatchBrowserEvent('success', [
                'message' => 'تم حذف حساب العميل بنجاح',
                'title' => 'حذف الحساب'
            ]);
        }else
        {
            $this->dispatchBrowserEvent('error', [
                'message' => 'يجب ان تكون حالة حساب العميل "غير مفعل" لاتمام عملية الحذف ',
                'title' => 'حذف الحساب'
            ]);
        }
    }
}
