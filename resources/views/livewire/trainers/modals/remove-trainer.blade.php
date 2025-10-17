<!-- Remove Trainer Modal -->
<div wire:ignore.self class="modal fade" id="removetrainerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="mb-4 text-center">
                        <i class="mdi mdi-trash-can-outline mdi-72px text-danger mb-4"></i>
                        <h4 class="mb-2">هل أنت متأكد؟</h4>
                        <p class="text-muted mx-4 mb-0">لن تتمكن من التراجع عن هذا!</p>
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="destroy, GetTrainer">
                    <div class="text-center col-12 demo-vertical-spacing mb-n4">
                        <button wire:click='destroy' type="button" class="btn btn-danger me-sm-3 me-1"
                            wire:loading.attr="disabled">نعم, احذف!</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                            aria-label="Close">تجاهل</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Remove Trainer Modal -->