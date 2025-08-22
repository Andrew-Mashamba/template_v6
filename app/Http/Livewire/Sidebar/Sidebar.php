<?php

namespace App\Http\Livewire\Sidebar;

use App\Models\RoleMenuAction;
use App\Models\Menu;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\MenuService;
use Illuminate\Support\Facades\Session;
class Sidebar extends Component
{
    public $tab_id = 0;
    public $currentUserId = null;
    public \Illuminate\Database\Eloquent\Collection $currentUserRoles;
    public $currentDepartment = null;
    public array $menuItems = [];
    public array $menuGroups = [];

    protected $listeners = ['refreshSidebar' => '$refresh'];

    private MenuService $menuService;

    public function __construct()
    {
        parent::__construct();
        $this->menuService = app(MenuService::class);
    }

    public function mount()
    {
        try {
            $this->loadUserRoles();

            //dd($this->currentUserRoles);
            $this->loadMenuItems();
        } catch (\Exception $e) {
            Log::error('Error in Sidebar mount', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error loading sidebar menu items.');
        }
    }

    protected function loadUserRoles()
    {
        try {
            $this->currentUserRoles = auth()->user()->roles()
                ->with(['permissions', 'department'])
                ->get();

            
            $this->currentDepartment = auth()->user()->department;
        } catch (\Exception $e) {
            Log::error('Error loading user roles', ['error' => $e->getMessage()]);
            $this->currentUserRoles = collect();
            $this->currentDepartment = null;
        }
    }

    public function loadMenuItems()
    {
        try {
            $this->currentUserId = auth()->id();
            $menuItems = collect();

            foreach ($this->currentUserRoles as $role) {
                $menuItems = $menuItems->merge($this->menuService->getMenuItemsForRole($role));
            }

            $permissions = $this->getUserPermissions();
            foreach ($permissions as $permission) {
                if (isset($permission['resource_type']) && 
                    $permission['resource_type'] === 'menu' && 
                    isset($permission['resource_id'])) {
                    $menuItems->push($permission['resource_id']);
                }
            }

            $this->menuItems = $menuItems->unique()->values()->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading menu items', ['error' => $e->getMessage()]);
            session()->flash('error', 'Error loading menu items.');
        }
    }

    public function menuItemClicked($menuId)
    {
       
        try {
            // Get menu and its actions
            $menu = Menu::find($menuId);
            if (!$menu && $menuId != 0) {
                throw new \Exception("Menu not found");
            }

            // Store active menu context in session
            session()->put('active_menu_id', $menuId);
            // session()->put('tab_id', $menuId);
            $this->tab_id = $menuId;
            
            // Emit event to System component
            $this->emit('menuItemClicked', $menuId);
            
            Log::info('Menu item clicked', [
                'menu_id' => $menuId,
                'menu_name' => $menu->menu_name,
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Log::error('Error in menuItemClicked', [
                'error' => $e->getMessage(),
                'menu_id' => $menuId
            ]);
            session()->flash('error', 'Failed to load menu item. Please try again.');
        }
    }

    private function getAggregatedPermissions($menuId)
    {
        try {
            $permissions = collect();

            foreach ($this->currentUserRoles as $role) {
                // Get direct permissions
                $rolePermissions = $role->permissions()
                    ->where('resource_type', 'menu')
                    ->where('resource_id', $menuId)
                    ->get();

                // Get inherited permissions if enabled
                if ($role->permission_inheritance_enabled) {
                    $inheritedPermissions = $role->getAllPermissions()
                        ->where('resource_type', 'menu')
                        ->where('resource_id', $menuId);
                    $rolePermissions = $rolePermissions->merge($inheritedPermissions);
                }

                // Get department-specific permissions
                if ($role->department_specific && $role->department) {
                    $departmentPermissions = $role->getDepartmentPermissions($role->department)
                        ->where('resource_type', 'menu')
                        ->where('resource_id', $menuId);
                    $rolePermissions = $rolePermissions->merge($departmentPermissions);
                }

                $permissions = $permissions->merge($rolePermissions);
            }

            return $permissions->unique('id')->values()->all();
        } catch (\Exception $e) {
            Log::error('Error getting aggregated permissions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getUserPermissions(): array
    {
        try {
            return Auth::user()->getAllPermissions()->toArray();
        } catch (Exception $e) {
            Log::error('Error fetching user permissions', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getGroupedMenuItems(): array
    {
        try {
            $groups = [];
            foreach ($this->menuItems as $menuId) {
                $menu = Menu::find($menuId);
                if (!$menu) continue;

                $group = $this->currentDepartment 
                    ? $this->currentDepartment->getFullPath() . ' > ' . $menu->group 
                    : $menu->group;

                if (!isset($groups[$group])) {
                    $groups[$group] = [];
                }
                $groups[$group][] = $menuId;
            }

            return $groups;
        } catch (Exception $e) {
            Log::error('Error grouping menu items', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function render()
    {
        try {
            $user = Auth::user();
            $this->currentUserId = $user->id;
            $this->currentUserRoles = $user->roles;

            if (empty($this->currentUserRoles)) {
                throw new Exception('User roles not found');
            }

            $permissions = $this->getUserPermissions();
            if (!empty($permissions)) {
                session()->put('permissions', $permissions);
                $this->menuGroups = $this->getGroupedMenuItems();
                session()->put('permission_items', collect($permissions)
                    ->pluck('allowed_actions')
                    ->flatten()
                    ->unique()
                    ->values()
                    ->toArray()
                );
            }

            return view('livewire.sidebar.sidebar');
        } catch (Exception $e) {
            Log::error('Error in Sidebar render', ['error' => $e->getMessage()]);
            $this->menuItems = [];
            $this->menuGroups = [];
            return view('livewire.sidebar.sidebar');
        }
    }
}
