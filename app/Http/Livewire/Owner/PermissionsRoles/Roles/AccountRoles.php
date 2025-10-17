<?php

namespace App\Http\Livewire\Owner\PermissionsRoles\Roles;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AccountRoles extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Roles = [];
    public $Permissions = [];
    public $name, $RoleID;
    public $TickAll, $CheckAll;
    public $SetPermission = [];
    public $searchPermission = '';

    public function mount()
    {
        /** @var \App\Models\User $user */
        $user = Auth::User();

        // تعديل التحقق من الصلاحيات
        if ($user->hasRole('OWNER') || $user->can('roles')) {
            $this->Permissions = Permission::orderBy('name', 'ASC')->get();
        } else {
            $this->Permissions = Permission::whereNotIn('name', [
                'permissions',
                'permission-list',
                'permission-create',
                'permission-edit',
                'permission-delete',
            ])->orderBy('name', 'ASC')->get();
        }
    }

    public function getFilteredPermissionsProperty()
    {
        return Permission::where('name', 'like', '%' . $this->searchPermission . '%')
            ->orWhere('explain_name', 'like', '%' . $this->searchPermission . '%')
            ->get();
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::User();

        // تعديل التحقق من الصلاحيات في العرض
        if ($user->hasRole('OWNER') || $user->can('roles')) {
            $Roles = Role::paginate(5);
        } else {
            $Roles = Role::whereNot('name', 'OWNER')->paginate(5);
        }

        $links = $Roles;
        $this->Roles = collect($Roles->items());

        return view('livewire.owner.permissions-roles.roles.account-roles', [
            'links' => $links
        ]);
    }

    public function create()
    {
        $this->name = '';
        $this->TickAll == false;
        $this->SetPermission = [];
        $this->resetValidation();
        $this->dispatchBrowserEvent('RoleModalShow');
    }

    public function TickPermission()
    {
        unset($this->SetPermission[array_search(false, $this->SetPermission)]);

        if(count($this->SetPermission) > 0)
        {
            $this->resetValidation();
        }else{
            $this->validate([
                'SetPermission' => 'required',
            ],
            [
                'SetPermission.required' => 'لا يمكن انشاء الادوار لعدم تحديد الصلاحيات',
            ]);
        }
    }

    public function CheckAll()
    {
        $this->resetValidation();

        $Permissions = Permission::with('roles')->get();
        if($this->TickAll == false)
        {
            $this->TickAll = true;
            foreach ($Permissions as $Permission)
            {
                $this->SetPermission[$Permission->id] = $Permission->name;
            }
        }else{
            $this->TickAll = false;
            foreach ($Permissions as $Permission)
            {
                $this->SetPermission = [];
            }
        }
    }

    public function store()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|unique:roles,name',
            'SetPermission' => 'required',
        ],
        [
            'name.required' => 'أسم الدور مطلوب',
            'name.unique' => 'أسم الدور مستخدم سابقاً',
            'SetPermission.required' => 'لا يمكن انشاء الادوار لعدم تحديد الصلاحيات',
        ]);

        if(count($this->SetPermission) > 0)
        {
            $Role = Role::create([
                'name' => $this->name,
                'guard_name' => 'web'
            ]);

            $Role = Role::find($Role->id);
            $Role->syncPermissions(  collect($this->SetPermission)  );

            $this->reset();
            $this->mount();

            $this->dispatchBrowserEvent('RoleAddSuccess');
        }else{
            $this->SetPermission = collect($this->SetPermission);
            $this->dispatchBrowserEvent('RoleAddError');
        }
    }

    public function GetRole($RoleID)
    {
        $this->resetValidation();
        $this->reset('SetPermission');

        $this->RoleID = $RoleID;
        $this->name = Role::find($RoleID)->name;

        $role_permissions = Role::where('name', $this->name)->first();

        if( count($role_permissions->permissions) == count(Permission::all()) )
        {
            $this->CheckAll = 'checked';
        }else{
            $this->CheckAll = '';
        }

        $Permissions = Permission::with('roles')->get();
        foreach ($Permissions as $Permission)
        {
            if(in_array($this->name, $Permission->roles->pluck('name')->toArray()))
            {
                $this->SetPermission[$Permission->id] = $Permission->name;
            }
        }
    }

    public function update()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|unique:roles,name,'.$this->RoleID,
            'SetPermission' => 'required',
        ],
        [
            'name.required' => 'أسم الدور مطلوب',
            'name.unique' => 'أسم الدور مستخدم سابقاً',
            'SetPermission.required' => 'لا يمكن تعديل الادوار لعدم تحديد الصلاحيات',
        ]);

        if(count($this->SetPermission) > 0)
        {
            $Role = Role::find($this->RoleID);
            $Role->update([
                'name' => $this->name
            ]);

            $Role->syncPermissions(  collect($this->SetPermission)  );

            $this->reset();
            $this->mount();

            $this->dispatchBrowserEvent('RoleUpdateSuccess');
        }else{
            $this->SetPermission = collect($this->SetPermission);
            $this->dispatchBrowserEvent('RoleAddError');
        }
    }

    public function destroy()
    {
        Role::find($this->RoleID)->delete();

        $this->mount();

        $this->dispatchBrowserEvent('RoleDestroySuccess');
    }
}
