<!-- org/modals/qr_modal.php -->
<div id="qrModal" class="fixed z-20 inset-0 overflow-y-auto hidden" aria-labelledby="qr-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeQRModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-center overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-center items-center mb-4 relative">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="qrModalTitle">Employee QR Code</h3>
                    <button onclick="closeQRModal()" class="absolute right-0 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-4 text-center" id="qrStudentName"></p>
                    <div id="qrcode" class="flex justify-center"></div>
                    <p class="text-xs text-gray-500 mt-4 text-center" id="qrData"></p>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-center">
                    <button onclick="downloadQR()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-teal-600 text-base font-medium text-white hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 sm:text-sm">
                        Download QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
