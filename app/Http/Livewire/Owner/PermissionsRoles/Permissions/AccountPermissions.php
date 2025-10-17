<?php

namespace App\Http\Livewire\Owner\PermissionsRoles\Permissions;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class AccountPermissions extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $Permissions = [];
    public $name, $explain_name, $PermissionID;
    public $search = ['name' => '', 'explain_name' => ''];

    public function updatedSearch($value, $key)
    {
        if (in_array($key, ['name', 'explain_name'])) {
            $this->resetPage();
        }
    }

    public function mount()
    {
        $this->Permissions = Permission::all();
    }

    public function render()
    {
        $searchName = '%' . $this->search['name'] . '%';
        $searchExplainName = '%' . $this->search['explain_name'] . '%';

        $Permissions = Permission::query()
            ->when($this->search['name'], function ($query) use ($searchName) {
                $query->where('name', 'LIKE', $searchName);
            })
            ->when($this->search['explain_name'], function ($query) use ($searchExplainName) {
                $query->where('explain_name', 'LIKE', $searchExplainName);
            })
            ->orderBy('name', 'ASC')
            ->paginate(10);

        $links = $Permissions;
        $this->Permissions = collect($Permissions->items());

        return view('livewire.owner.permissions-roles.permissions.account-permissions', [
            'links' => $links
        ]);
    }

    public function AddPermissionModalShow()
    {
        $this->reset();
        $this->mount();
        $this->resetValidation();
        $this->dispatchBrowserEvent('PermissionModalShow');
    }

    public function store()
    {
        $this->resetValidation();

        $this->validate([
            'name' => 'required|unique:permissions,name',
        ], [
            'name.required' => 'أسم التصريح مطلوب',
            'name.unique' => 'أسم التصريج مستخدم سابقاً',
        ]);

        Permission::create([
            'name' => $this->name,
            'explain_name' => $this->explain_name,
            'guard_name' => 'web'
        ]);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('PermissionAddSuccess');
    }

    public function GetPermission($PermissionID)
    {
        $this->resetValidation();

        $this->PermissionID = $PermissionID;
        $Permission = Permission::find($PermissionID);
        $this->name = $Permission->name;
        $this->explain_name = $Permission->explain_name;
    }

    public function update()
    {
        $this->resetValidation();
        $this->validate([
            'name' => 'required|unique:permissions,name,'.$this->PermissionID,
        ], [
            'name.required' => 'أسم التصريح مطلوب',
            'name.unique' => 'أسم التصريح مستخدم سابقاً',
        ]);

        $Permission = Permission::find($this->PermissionID);
        $Permission->update([
            'name' => $this->name,
            'explain_name' => $this->explain_name
        ]);

        $this->reset();
        $this->mount();

        $this->dispatchBrowserEvent('PermissionUpdateSuccess');
    }

    public function destroy()
    {
        Permission::find($this->PermissionID)->delete();

        $this->mount();

        $this->dispatchBrowserEvent('PermissionDestroySuccess');
    }
}
