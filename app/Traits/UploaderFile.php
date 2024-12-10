<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

trait UploaderFile
{
    /**
     * Upload a file and handle it according to its type.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     */
    public function uploadFile($file)
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file upload.');
        }

        $extension = $file->getClientOriginalExtension();


        return ($extension === 'pdf') ? $this->handlePdf($file) : $this->handleImage($file);
    }

    /**
     * Handle PDF file upload.
     *
     * @param \Illuminate\Http\UploadedFile $pdf
     * @return string
     */
    protected function handlePdf($pdf)
    {
        if (!Storage::exists('tmp')) {
            Storage::makeDirectory('tmp');
        }
        $pdfName = uniqid() . '.pdf';
        $pdfPath = "tmp/$pdfName";
        Storage::put($pdfPath, $pdf->getContent());

        return Storage::url($pdfPath);
    }

    /**
     * Handle image file upload and convert it to WebP format.
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string
     */
    protected function handleImage($image)
    {
        $imageName = uniqid() . '.webp';
        $imagePath = "tmp/{$imageName}";

        // Read and convert the image to WebP format
        Image::read($image)
            ->toWebp()
            ->save(Storage::path($imagePath));

        return Storage::url($imagePath);
    }

    /**
     * Delete an uploaded file.
     *
     * @param string $path
     * @return void
     */
    public function deleteFile($path)
    {
        Storage::delete($path);
    }
}
