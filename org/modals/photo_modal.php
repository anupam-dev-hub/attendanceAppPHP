<!-- org/modals/photo_modal.php -->
<div id="photoModal" class="fixed z-30 inset-0 overflow-y-auto hidden" aria-labelledby="photo-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-90 transition-opacity" aria-hidden="true" onclick="closePhotoModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-middle bg-transparent rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-3xl sm:w-full">
            <div class="relative">
                <button onclick="closePhotoModal()" class="absolute top-0 right-0 -mt-12 -mr-12 text-white hover:text-gray-300 focus:outline-none">
                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="text-center mb-4">
                    <h3 class="text-2xl font-bold text-white" id="photoModalTitle">Student Name</h3>
                </div>
                <img id="fullSizePhoto" src="" alt="Full Size Photo" class="w-full h-auto max-h-[80vh] object-contain rounded-lg mx-auto">
                <div class="mt-4 flex justify-center">
                    <a id="photoDownloadLink" href="#" download class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download Photo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
