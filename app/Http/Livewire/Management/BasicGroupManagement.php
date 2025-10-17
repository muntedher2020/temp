<?php

namespace App\Http\Livewire\Management;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;
use App\Services\DynamicMenuService;
use App\Models\Management\BasicGroup;
use Illuminate\Validation\ValidationException;

class BasicGroupManagement extends Component
{
    use WithPagination;

    // Search and Filter
    public $search = '';
    public $statusFilter = '';
    public $perPage = 15;

    // Form Data
    public $basicGroupId;
    public $name_en = '';
    public $name_ar = '';
    public $icon = 'mdi mdi-folder-outline';
    public $description_en = '';
    public $description_ar = '';
    public $status = true;
    public $sort_order;
    public $route = '';
    public $type = 'group';

    // Modal States
    public $showModal = false;
    public $showDeleteModal = false;
    public $isEditing = false;

    // Icon Preview
    public $iconPreview = '';
    public $showIconPicker = false;

    // Selected Item for Actions
    public $selectedItem;

    protected $queryString = ['search', 'statusFilter', 'perPage'];

    protected $listeners = [
        'refreshComponent' => '$refresh',
        'closeModal' => 'closeModal',
    ];

    public function mount()
    {
        $this->iconPreview = $this->icon;
    }

    public function rules()
    {
        $rules = [
            'name_en' => [
                'required',
                'string',
                'max:255',
                Rule::unique('basic_groups', 'name_en')->ignore($this->basicGroupId)
            ],
            'name_ar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('basic_groups', 'name_ar')->ignore($this->basicGroupId)
            ],
            'icon' => 'required|string|max:255',
            'description_en' => 'nullable|string|max:500',
            'description_ar' => 'nullable|string|max:500',
            'status' => 'boolean',
            'sort_order' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('basic_groups', 'sort_order')->ignore($this->basicGroupId)
            ],
            'route' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($this->type === 'item'),
                Rule::unique('basic_groups', 'route')->ignore($this->basicGroupId)
            ],
            'type' => 'required|in:group,item',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'name_en.required' => 'الاسم الإنجليزي مطلوب',
            'name_en.unique' => 'الاسم الإنجليزي موجود بالفعل',
            'name_ar.required' => 'الاسم العربي مطلوب',
            'name_ar.unique' => 'الاسم العربي موجود بالفعل',
            'icon.required' => 'الأيقونة مطلوبة',
            'sort_order.required' => 'ترتيب العرض مطلوب',
            'sort_order.unique' => 'ترتيب العرض موجود بالفعل، يرجى اختيار رقم آخر',
            'sort_order.integer' => 'ترتيب العرض يجب أن يكون رقماً صحيحاً',
            'sort_order.min' => 'ترتيب العرض يجب أن يكون أكبر من أو يساوي 0',
            'route.required' => 'المسار مطلوب للعناصر المستقلة',
            'route.unique' => 'المسار موجود بالفعل، يرجى اختيار مسار آخر',
            'type.required' => 'نوع العنصر مطلوب',
            'type.in' => 'نوع العنصر يجب أن يكون مجموعة أو عنصر مستقل',
        ];
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'icon') {
            $this->iconPreview = $this->icon;
        }

        if (in_array($propertyName, ['search', 'statusFilter', 'perPage'])) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $basicGroups = BasicGroup::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name_en', 'like', '%' . $this->search . '%')
                      ->orWhere('name_ar', 'like', '%' . $this->search . '%')
                      ->orWhere('description_en', 'like', '%' . $this->search . '%')
                      ->orWhere('description_ar', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->withTrashed()
            ->ordered()
            ->paginate($this->perPage);

        return view('livewire.management.basic-group-management', compact('basicGroups'));
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->sort_order = BasicGroup::getSuggestedSortOrder();
        $this->showModal = true;
    }

    public function suggestSortOrder()
    {
        $this->sort_order = BasicGroup::getSuggestedSortOrder();

        $this->dispatchBrowserEvent('info', [
            'title' => 'تم الاقتراح!',
            'message' => "تم اقتراح الرقم {$this->sort_order} كترتيب عرض متاح"
        ]);
    }

    public function edit($id)
    {
        $basicGroup = BasicGroup::withTrashed()->findOrFail($id);

        $this->basicGroupId = $basicGroup->id;
        $this->name_en = $basicGroup->name_en;
        $this->name_ar = $basicGroup->name_ar;
        $this->icon = $basicGroup->icon;
        $this->description_en = $basicGroup->description_en;
        $this->description_ar = $basicGroup->description_ar;
        $this->status = $basicGroup->status;
        $this->sort_order = $basicGroup->sort_order;
        $this->route = $basicGroup->route ?? '';
        $this->type = $basicGroup->type ?? 'group';

        $this->iconPreview = $this->icon;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        try {
            $this->validate();
        } catch (ValidationException $e) {
            $errors = collect($e->errors())->flatten()->implode(' - ');
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ في البيانات!',
                'message' => 'يرجى تصحيح الأخطاء التالية: ' . $errors
            ]);
            return;
        }

        $data = [
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'icon' => $this->icon,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'type' => $this->type,
            'route' => $this->type === 'item' ? $this->route : null,
            'permission' => $this->type === 'item' ? $this->route : $this->name_en,
            'active_routes' => $this->type === 'item' ? $this->route : $this->name_en,
        ];

        if ($this->isEditing) {
            $basicGroup = BasicGroup::withTrashed()->find($this->basicGroupId);
            $oldStatus = $basicGroup->status;
            $basicGroup->update($data);

            // تحديث القائمة الديناميكية
            if ($basicGroup->status) {
                DynamicMenuService::updateMenuForGroup($basicGroup, 'update');
            } elseif ($oldStatus && !$basicGroup->status) {
                DynamicMenuService::updateMenuForGroup($basicGroup, 'delete');
            }

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم بنجاح!',
                'message' => 'تم تحديث المجموعة الأساسية والقائمة الرئيسية بنجاح'
            ]);
        } else {
            $basicGroup = BasicGroup::create($data);

            // إضافة للقائمة الديناميكية إذا كانت مفعلة
            if ($basicGroup->status) {
                DynamicMenuService::updateMenuForGroup($basicGroup, 'create');
            }

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم بنجاح!',
                'message' => 'تم إنشاء المجموعة الأساسية وإضافتها للقائمة الرئيسية بنجاح'
            ]);
        }

        $this->closeModal();
        $this->emit('refreshComponent');
    }

    public function confirmDelete($id)
    {
        $this->selectedItem = BasicGroup::withTrashed()->findOrFail($id);
        $this->showDeleteModal = true;

        // يمكن أيضاً إضافة SweetAlert للتأكيد
        /*
        $this->dispatchBrowserEvent('confirm-delete', [
            'title' => 'هل أنت متأكد؟',
            'message' => $this->selectedItem->trashed()
                ? 'سيتم حذف المجموعة نهائياً ولا يمكن التراجع عن هذا الإجراء'
                : 'سيتم نقل المجموعة إلى سلة المحذوفات',
            'confirmButtonText' => $this->selectedItem->trashed() ? 'نعم، احذف نهائياً' : 'نعم، احذف',
            'itemId' => $id
        ]);
        */
    }

    public function delete()
    {
        if ($this->selectedItem->trashed()) {
            // حذف نهائي
            DynamicMenuService::updateMenuForGroup($this->selectedItem, 'delete');
            $this->selectedItem->forceDelete();

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم الحذف!',
                'message' => 'تم حذف المجموعة الأساسية نهائياً من النظام والقائمة'
            ]);
        } else {
            // حذف ناعم
            $this->selectedItem->delete();
            DynamicMenuService::updateMenuForGroup($this->selectedItem, 'delete');

            $this->dispatchBrowserEvent('info', [
                'title' => 'تم النقل!',
                'message' => 'تم نقل المجموعة الأساسية إلى سلة المحذوفات وحذفها من القائمة'
            ]);
        }

        $this->closeModal();
        $this->emit('refreshComponent');
    }

    public function restore($id)
    {
        $basicGroup = BasicGroup::withTrashed()->findOrFail($id);
        $basicGroup->restore();

        // إضافة للقائمة الديناميكية إذا كانت مفعلة (مع استعادة الوحدات الفرعية)
        if ($basicGroup->status) {
            DynamicMenuService::updateMenuForGroup($basicGroup, 'restore');
        }

        // عد الوحدات الفرعية التي تم استعادتها
        $subModulesCount = $this->countSubModulesForGroup($basicGroup);

        if ($subModulesCount > 0) {
            $this->dispatchBrowserEvent('success', [
                'title' => 'تمت الاستعادة! 🎉',
                'message' => "تم استعادة المجموعة الأساسية مع {$subModulesCount} وحدة فرعية وإضافتها للقائمة الرئيسية"
            ]);
        } else {
            $this->dispatchBrowserEvent('success', [
                'title' => 'تمت الاستعادة!',
                'message' => 'تم استعادة المجموعة الأساسية وإضافتها للقائمة الرئيسية'
            ]);
        }

        $this->emit('refreshComponent');
    }

    public function toggleStatus($id)
    {
        $basicGroup = BasicGroup::find($id);
        if ($basicGroup) {
            $oldStatus = $basicGroup->status;
            // تبديل الحالة: من true إلى false أو العكس
            $basicGroup->status = !$basicGroup->status;
            $basicGroup->save();

            // استخدام الـ actions الجديدة للتعامل مع التعطيل/التفعيل
            if ($basicGroup->status) {
                // تفعيل: استعادة الصلاحيات والوحدات الفرعية
                DynamicMenuService::updateMenuForGroup($basicGroup, 'enable');
                $this->showSuccessAlert(
                    'تم تفعيل المجموعة بنجاح!',
                    "تم تفعيل مجموعة '{$basicGroup->name_ar}' مع استعادة جميع الصلاحيات والوحدات الفرعية."
                );
            } else {
                // تعطيل: حفظ احتياطي للصلاحيات وإخفاء الوحدات الفرعية
                DynamicMenuService::updateMenuForGroup($basicGroup, 'disable');
                $this->showSuccessAlert(
                    'تم تعطيل المجموعة بنجاح!',
                    "تم تعطيل مجموعة '{$basicGroup->name_ar}' مع حفظ الصلاحيات والوحدات الفرعية للاستعادة لاحقاً."
                );
            }

            // تحديث البيانات في الواجهة بدلاً من resetForm
            $this->emit('refreshComponent');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->showDeleteModal = false;
        $this->showIconPicker = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function resetForm()
    {
        $this->basicGroupId = null;
        $this->name_en = '';
        $this->name_ar = '';
        $this->icon = 'mdi mdi-folder-outline';
        $this->description_en = '';
        $this->description_ar = '';
        $this->status = true;
        $this->sort_order = null;
        $this->route = '';
        $this->type = 'group';
        $this->iconPreview = 'mdi mdi-folder-outline';
    }

    public function selectIcon($icon)
    {
        $this->icon = $icon;
        $this->iconPreview = $icon;
        $this->showIconPicker = false;
    }

    public function openIconPicker()
    {
        $this->showIconPicker = true;
    }

    // Icon Categories and Icons
    public function getIconCategories()
    {
        return [
            'عام' => [
                'mdi mdi-folder-outline',
                'mdi mdi-folder',
                'mdi mdi-home',
                'mdi mdi-office-building',
                'mdi mdi-account-group',
                'mdi mdi-cog',
                'mdi mdi-view-dashboard',
                'mdi mdi-chart-box',
                'mdi mdi-file-document',
                'mdi mdi-database',
            ],
            'أعمال' => [
                'mdi mdi-briefcase',
                'mdi mdi-currency-usd',
                'mdi mdi-chart-line',
                'mdi mdi-trending-up',
                'mdi mdi-calculator',
                'mdi mdi-receipt',
                'mdi mdi-credit-card',
                'mdi mdi-bank',
                'mdi mdi-handshake',
                'mdi mdi-store',
            ],
            'أشخاص' => [
                'mdi mdi-account',
                'mdi mdi-account-multiple',
                'mdi mdi-account-group',
                'mdi mdi-account-tie',
                'mdi mdi-account-supervisor',
                'mdi mdi-human-greeting',
                'mdi mdi-face-agent',
                'mdi mdi-badge-account',
                'mdi mdi-id-card',
                'mdi mdi-contacts',
            ],
            'تقنية' => [
                'mdi mdi-laptop',
                'mdi mdi-server',
                'mdi mdi-code-tags',
                'mdi mdi-web',
                'mdi mdi-database-settings',
                'mdi mdi-api',
                'mdi mdi-cloud',
                'mdi mdi-monitor',
                'mdi mdi-cellphone',
                'mdi mdi-wifi',
            ],
        ];
    }

    /**
     * تزامن القائمة الديناميكية مع المجموعات الأساسية
     */
    public function syncMenu()
    {
        try {
            DynamicMenuService::syncAllBasicGroups();

            $this->dispatchBrowserEvent('success', [
                'title' => 'تم التحديث!',
                'message' => 'تم تحديث القائمة الرئيسية وتزامنها مع جميع المجموعات الأساسية بنجاح'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ!',
                'message' => 'حدث خطأ أثناء تحديث القائمة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * إضافة زر لإعادة فحص وربط الوحدات المفقودة بالمجموعات
     */
    public function rescanAndRestoreMissingModules()
    {
        try {
            $restoredCount = DynamicMenuService::rescanAndRestoreAllMissingModules();

            if ($restoredCount > 0) {
                $this->dispatchBrowserEvent('success', [
                    'title' => 'تمت الاستعادة! 🔄',
                    'message' => "تم العثور على {$restoredCount} وحدة مفقودة وإعادة ربطها بالمجموعات الأساسية"
                ]);
            } else {
                $this->dispatchBrowserEvent('info', [
                    'title' => 'مكتمل ✓',
                    'message' => 'جميع الوحدات مرتبطة بشكل صحيح، لا توجد وحدات مفقودة'
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('error', [
                'title' => 'خطأ!',
                'message' => 'حدث خطأ أثناء فحص الوحدات: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * عد الوحدات الفرعية لمجموعة أساسية
     */
    private function countSubModulesForGroup(BasicGroup $basicGroup): int
    {
        $count = 0;
        $moduleConfigsPath = storage_path('app/modules_config');

        if (!is_dir($moduleConfigsPath)) {
            return $count;
        }

        $configFiles = glob($moduleConfigsPath . '/*.json');

        foreach ($configFiles as $configFile) {
            try {
                $config = json_decode(file_get_contents($configFile), true);

                if (isset($config['parent_group']) && $config['parent_group'] === $basicGroup->name_en) {
                    $moduleName = pathinfo($configFile, PATHINFO_FILENAME);

                    // التحقق من وجود ملفات الوحدة
                    $controllerPath = app_path("Http/Controllers/{$moduleName}/{$moduleName}Controller.php");
                    $livewirePath = app_path("Http/Livewire/{$moduleName}/{$moduleName}.php");

                    if (file_exists($controllerPath) && file_exists($livewirePath)) {
                        $count++;
                    }
                }
            } catch (\Exception $e) {
                // تجاهل الأخطاء في قراءة الملفات
            }
        }

        return $count;
    }

    /**
     * عرض رسالة نجاح مخصصة
     */
    private function showSuccessAlert($title, $message)
    {
        $this->dispatchBrowserEvent('success', [
            'title' => $title,
            'message' => $message
        ]);
    }
}
