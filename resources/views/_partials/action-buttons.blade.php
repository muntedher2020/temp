<div class="btn-group" role="group" aria-label="First group">
    @can('boycott-edit')
        <button wire:click="GetBoycott({{ $row->id }})"
            class="p-0 px-1 btn btn-text-success waves-effect" data-bs-toggle="modal"
            data-bs-target="#editboycottModal">
            <i class="tf-icons mdi mdi-pencil fs-3"></i>
        </button>
    @endcan
    @can('boycott-delete')
        <button wire:click="GetBoycott({{ $row->id }})"
            class="p-0 px-1 btn btn-text-danger waves-effect {{ $row->active ? 'disabled' : '' }}"
            data-bs-toggle="modal" data-bs-target="#removeboycottModal">
            <i class="tf-icons mdi mdi-delete-outline fs-3"></i>
        </button>
    @endcan
</div>
