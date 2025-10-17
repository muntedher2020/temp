<!-- Remove Report Modal -->
<div wire:ignore.self class="modal fade" id="removeReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="p-4 modal-content p-md-5">
            <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-md-0">
                <div class="mb-4 text-center mt-n4">
                    <div class="mb-4 text-center">
                        <i class="mdi mdi-trash-can-outline mdi-72px text-danger mb-4"></i>
                        <h4 class="mb-2">هل أنت متأكد؟</h4>
                        <p class="text-muted mx-4 mb-2">سيتم حذف التقرير نهائياً من قاعدة البيانات ولن تتمكن من التراجع عن هذا!</p>
                        @if($reportToDelete)
                            @php
                                $reportToDeleteObj = App\Models\ReportGenerator\ReportGenerator::find($reportToDelete);
                            @endphp
                            @if($reportToDeleteObj)
                                <div class="alert alert-warning mt-3">
                                    <strong>التقرير المراد حذفه:</strong> {{ $reportToDeleteObj->title }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
                <hr class="mt-n2">
                <div wire:loading.remove wire:target="confirmDeleteReport, getReportForDeletion">
                    <div class="text-center col-12 demo-vertical-spacing mb-n4">
                        <button onclick="confirmDeleteReportDirect()" type="button" class="btn btn-danger me-sm-3 me-1">نعم, احذف نهائياً!</button>
                        <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal"
                            aria-label="Close">تجاهل</button>
                    </div>
                </div>

                <!-- Loading State -->
                <div wire:loading wire:target="confirmDeleteReport, getReportForDeletion" class="text-center">
                    <div class="spinner-border text-danger" role="status">
                        <span class="visually-hidden">جاري الحذف...</span>
                    </div>
                    <p class="mt-2 text-muted">جاري حذف التقرير...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Remove Report Modal -->
