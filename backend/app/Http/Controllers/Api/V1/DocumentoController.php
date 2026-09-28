<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documentos\CreateDocumentoAction;
use App\Actions\Documentos\DeleteDocumentoAction;
use App\Actions\Documentos\UpdateDocumentoAction;
use App\Contracts\DocumentoPrivateStorage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documento\CreateDocumentoRequest;
use App\Http\Requests\Documento\IndexDocumentoRequest;
use App\Http\Requests\Documento\UpdateDocumentoRequest;
use App\Http\Resources\DocumentoResource;
use App\Models\Documento;
use App\Queries\Documentos\DocumentoIndexQuery;
use App\Queries\Visibility\VisibleDocumentosQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function index(IndexDocumentoRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Documento::class);

        $filters = $request->validated();
        $query = (new VisibleDocumentosQuery($request->user()))->apply(Documento::query());
        $query = (new DocumentoIndexQuery($filters))->apply($query);
        $perPage = (int) ($filters['per_page'] ?? 15);

        return DocumentoResource::collection(
            $query->with($this->safeRelations())->paginate($perPage)->withQueryString()
        );
    }

    public function store(CreateDocumentoRequest $request, CreateDocumentoAction $action): JsonResponse
    {
        Gate::authorize('create', Documento::class);

        $documento = $action->execute($request->user(), $request->file('archivo'), $request->validated());
        $documento->load($this->safeRelations());

        return (new DocumentoResource($documento))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Documento $documento): DocumentoResource
    {
        Gate::authorize('view', $documento);
        $documento->load($this->safeRelations());

        return new DocumentoResource($documento);
    }

    public function update(UpdateDocumentoRequest $request, Documento $documento, UpdateDocumentoAction $action): DocumentoResource
    {
        Gate::authorize('update', $documento);

        $documento = $action->execute($documento, $request->validated(), $request->user());
        $documento->load($this->safeRelations());

        return new DocumentoResource($documento);
    }

    public function destroy(Documento $documento, DeleteDocumentoAction $action): Response
    {
        Gate::authorize('delete', $documento);
        $action->execute($documento, request()->user());

        return response()->noContent();
    }

    public function download(Documento $documento, DocumentoPrivateStorage $storage): StreamedResponse
    {
        Gate::authorize('view', $documento);

        $mimeType = $documento->mime_type?->value ?? (string) $documento->mime_type;
        $size = $storage->size($documento->firebase_path);
        $filename = $this->safeDownloadName($documento->nombre_original);
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        if ($size !== null) {
            $headers['Content-Length'] = (string) $size;
        }

        return response()->streamDownload(
            function () use ($documento, $storage): void {
                $storage->stream($documento->firebase_path, static function (string $chunk): void {
                    echo $chunk;
                });
            },
            $filename,
            $headers,
        );
    }

    /**
     * @return array<string, string>
     */
    private function safeRelations(): array
    {
        return [
            'categoriaDocumento:id,nombre,activo',
            'subidoPor:id,nombres,apellido_paterno,apellido_materno',
            'propietario:id,nombre_razon_social',
            'cliente:id,nombres,apellido_paterno,apellido_materno',
            'inmueble:id,codigo,titulo,slug,publicado',
            'operacion:id,tipo_operacion,monto,estado',
        ];
    }

    private function safeDownloadName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return trim($name) !== '' ? trim($name) : 'documento';
    }
}
