<?php

namespace App\Http\Livewire\Users\AdministratorsAccounts;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdministratorsAccounts extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Administrators = [];
    public $Roles = [];
    public $AdministratorRolesName = [];
    public $AdministratorRoles = [];
    public $AdministratorAccount;
    public $AdministratorId, $name, $email, $password, $ConfirmPassword, $status, $plan;
    public $SearchName, $SearchEmail, $SearchRole, $SearchStatus;
    public $RoleSelect, $StatusSelect;
    public $search = [
        'name' => '',
        'email' => '',
        'role' => '',
        'status' => null
    ];

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

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['name', 'email', 'role', 'status'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $this->Roles = Role::all();

        $Administrators = User::whereIn('plan', ['Supervisor', 'Employee', 'OWNER'])
            ->when($this->search['name'], function ($query) {
                $query->where('name', 'LIKE', '%' . $this->search['name'] . '%');
            })
            ->when($this->search['email'], function ($query) {
                $query->where('email', 'LIKE', '%' . $this->search['email'] . '%');
            })
            ->when($this->search['role'], function ($query) {
                $roleIds = DB::table('model_has_roles')
                    ->where('role_id', $this->search['role'])
                    ->pluck('model_id');
                $query->whereIn('id', $roleIds);
            })
            ->when(!is_null($this->search['status']), function ($query) {
                if ($this->search['status'] === 'متصل') {
                    $query->where('status', 1)
                        ->whereHas('onlineSessions', function ($q) {
                            $q->where('last_activity', '>=', now()->subMinutes(5));
                        });
                } elseif ($this->search['status'] === 'غير متصل') {
                    $query->where('status', 1)
                        ->whereDoesntHave('onlineSessions', function ($q) {
                            $q->where('last_activity', '>=', now()->subMinutes(5));
                        });
                } elseif ($this->search['status'] === 'مفعل - متصل') {
                    $query->where('status', 1)
                        ->whereHas('onlineSessions', function ($q) {
                            $q->where('last_activity', '>=', now()->subMinutes(5));
                        });
                } elseif ($this->search['status'] === 'مفعل - غير متصل') {
                    $query->where('status', 1)
                        ->whereDoesntHave('onlineSessions', function ($q) {
                            $q->where('last_activity', '>=', now()->subMinutes(5));
                        });
                } elseif (in_array($this->search['status'], [1, 0, '1', '0'])) {
                    $query->where('status', $this->search['status']);
                }
            })
            ->orderBy('id', 'ASC')
            ->paginate(10);

        $links = $Administrators;
        $this->Administrators = collect($Administrators->items());

        return view('livewire.users.administrators-accounts.administrators-accounts', [
            'links' => $links
        ]);
    }

    public function SelectAdministratorRoles($AdministratorRolesId)
    {
        if ($AdministratorRolesId) {
            $this->RoleSelect = $AdministratorRolesId;
        } else {
            $this->reset('RoleSelect');
        }
    }
    public function SelectAdministratorStatus($AdministratorStatus)
    {
        if ($AdministratorStatus) {
            $this->StatusSelect = $AdministratorStatus;
        } else {
            $this->reset('StatusSelect');
        }
    }

    public function GetAdministratorsAccount($AdministratorId)
    {
        $this->resetValidation();
        $this->reset('name', 'email', 'plan', 'status', 'AdministratorRolesName');

        $this->AdministratorAccount = User::find($AdministratorId);
        $this->AdministratorId = $this->AdministratorAccount->id;
        $this->AdministratorRolesName = $this->AdministratorAccount->getRoleNames();
        $this->AdministratorRoles = $this->AdministratorAccount->roles->pluck('id')->toArray();

        $this->name = $this->AdministratorAccount->name;
        $this->email = $this->AdministratorAccount->email;
        $this->status = $this->AdministratorAccount->status;
        $this->plan = $this->AdministratorAccount->plan;
    }

    public function AdministratorsAccountAdd()
    {
        $this->resetValidation();
        $this->reset('name', 'email', 'password', 'status', 'AdministratorRoles');
        $this->mount();
    }

    public function store()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|min:3|unique:users,name',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:ConfirmPassword',
            'AdministratorRoles' => 'required',
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
            'AdministratorRoles.required' => 'حقل دور المشرف مطلوب',
            'plan.required' => 'خطة العمل مطلوب',
            'status.required' => 'حقل حالة المشرف مطلوب',
        ]);

        $User = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'status' => $this->status,
            'plan' => $this->plan,
        ]);

        $User->assignRole($this->AdministratorRoles);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('success', [
            'message' => 'تم اضافة المشرف بنجاح',
            'title' => 'اضافة مشرف'
        ]);
    }

    public function update()
    {
        $this->resetValidation();

        $this->validate(
            [
                'name' => 'required|unique:users,name,' . $this->AdministratorId,
                'email' => 'required|email|unique:users,email,' . $this->AdministratorId,
                'password' => 'nullable|min:8|same:ConfirmPassword',
                'AdministratorRoles' => 'required',
                'plan' => 'required',
                'status' => 'required',
            ],
            [
                'name.required' => 'أسم المشرف مطلوب.',
                'name.unique' => 'تم أخذ أسم المشرف بالفعل',
                'email.required' => 'البريد الالكتروني مطلوب.',
                'email.email' => 'يجب التأكد من البريد الالكتروني.',
                'email.unique' => 'البريد الالكتروني مسجل سابقاً',
                'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف',
                'password.same' => 'يجب أن تتطابق كلمة المرور مع تأكيد كلمة المرور',
                'AdministratorRoles.required' => 'دور المشرف مطلوب.',
                'plan.required' => 'يجب تحديد خطة المشرف مطلوب.',
                'status.required' => 'يجب تحديد حالة المشرف مطلوب.',
            ]
        );

        $Administrator = User::find($this->AdministratorId);

        if (!empty($this->password)) {
            $this->password =  Hash::make($this->password);
        } else {
            $this->password = $Administrator->password;
        }

        $Administrator->update([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'status' => $this->status,
            'plan' => $this->plan,
        ]);

        DB::table('model_has_roles')->where('model_id', $this->AdministratorId)->delete();
        $Administrator->assignRole($this->AdministratorRoles);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('success', [
            'message' => 'تم تعديل حساب المشرف بنجاح',
            'title' => 'تعديل حساب مشرف'
        ]);
    }

    public function destroy()
    {
        $User = User::find($this->AdministratorId);
        if (!$User->status) {
            User::find($this->AdministratorId)->delete();

            $this->reset();
            $this->mount();
            $this->dispatchBrowserEvent('success', [
                'message' => 'تم حذف حساب المشرف بنجاح',
                'title' => 'حذف حساب مشرف'
            ]);
        } else {
            $this->dispatchBrowserEvent('success', [
                'message' => 'يجب ان تكون حالة المشرف "غير مفعل" لاتمام عملية الحذف ',
                'title' => 'حساب مشرف'
            ]);
        }
    }
}
