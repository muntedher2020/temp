<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Management\BasicGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\PermissionHelper;
use App\Services\DynamicMenuService;

class BasicGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:BasicGroup-list|BasicGroup-create|BasicGroup-edit|BasicGroup-delete', ['only' => ['index', 'show']]);
        $this->middleware('permission:BasicGroup-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:BasicGroup-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:BasicGroup-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = PermissionHelper::getPermissions('BasicGroup');

        return view('content.basic-groups.index', compact('permissions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('content.basic-groups.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255|unique:basic_groups',
            'name_ar' => 'required|string|max:255|unique:basic_groups',
            'icon' => 'required|string|max:255',
            'description_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:500',
            'status' => 'boolean',
        ]);

        $validated['sort_order'] = BasicGroup::getNextSortOrder();

        $basicGroup = BasicGroup::create($validated);

        // تحديث القائمة الديناميكية
        if ($basicGroup->status) {
            DynamicMenuService::updateMenuForGroup($basicGroup, 'create');
        }

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم إنشاء المجموعة الأساسية بنجاح وإضافتها للقائمة الرئيسية');
    }

    /**
     * Display the specified resource.
     */
    public function show(BasicGroup $basicGroup)
    {
        return view('content.basic-groups.show', compact('basicGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BasicGroup $basicGroup)
    {
        return view('content.basic-groups.edit', compact('basicGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BasicGroup $basicGroup)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255|unique:basic_groups,name_en,' . $basicGroup->id,
            'name_ar' => 'required|string|max:255|unique:basic_groups,name_ar,' . $basicGroup->id,
            'icon' => 'required|string|max:255',
            'description_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:500',
            'status' => 'boolean',
        ]);

        $oldStatus = $basicGroup->status;
        $basicGroup->update($validated);

        // تحديث القائمة الديناميكية
        if ($basicGroup->status) {
            DynamicMenuService::updateMenuForGroup($basicGroup, 'update');
        } elseif ($oldStatus && !$basicGroup->status) {
            // إذا تم إلغاء تفعيل المجموعة، احذفها من القائمة
            DynamicMenuService::updateMenuForGroup($basicGroup, 'delete');
        }

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم تحديث المجموعة الأساسية والقائمة الرئيسية بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BasicGroup $basicGroup)
    {
        $basicGroup->delete();

        // حذف المجموعة من القائمة الديناميكية
        DynamicMenuService::updateMenuForGroup($basicGroup, 'delete');

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم حذف المجموعة الأساسية من النظام والقائمة الرئيسية');
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore($id)
    {
        $basicGroup = BasicGroup::withTrashed()->findOrFail($id);
        $basicGroup->restore();

        // إضافة المجموعة للقائمة الديناميكية مرة أخرى إذا كانت مفعلة
        if ($basicGroup->status) {
            DynamicMenuService::updateMenuForGroup($basicGroup, 'restore');
        }

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم استعادة المجموعة الأساسية وإضافتها للقائمة الرئيسية');
    }

    /**
     * Force delete the specified resource from storage.
     */
    public function forceDelete($id)
    {
        $basicGroup = BasicGroup::withTrashed()->findOrFail($id);

        // حذف المجموعة من القائمة الديناميكية نهائياً
        DynamicMenuService::updateMenuForGroup($basicGroup, 'delete');

        $basicGroup->forceDelete();

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم حذف المجموعة الأساسية نهائياً من النظام والقائمة');
    }

    /**
     * تزامن جميع المجموعات مع القائمة الديناميكية
     */
    public function syncMenu()
    {
        DynamicMenuService::syncAllBasicGroups();

        return redirect()->route('basic-groups.index')
                        ->with('success', 'تم تحديث القائمة الرئيسية وتزامنها مع جميع المجموعات الأساسية');
    }
}
