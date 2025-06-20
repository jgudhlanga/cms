<?php

namespace App\Traits;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

trait HttpUtil
{
    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function success($data = null, int $code = Response::HTTP_OK, string $message = "Request was successful"): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'result' => $data], $code);
    }

    /**
     * @param string $message
     * @param $error
     * @param int $code
     * @return JsonResponse
     */
    protected function error(string $message = "Request failed", $error = null, int $code = Response::HTTP_INTERNAL_SERVER_ERROR): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $error,], $code);
    }

    protected function getRequestData(Request $request, array $columns): array
    {
        $values = [];
        foreach ($request->all() as $key => $value) {
            if ((!in_array($key, $columns)) || $key == 'image') {
                continue;
            }
            $values[$key] = ($value !== '') ? $value : NULL;
        }
        return $values;
    }

    protected function uploadImage(Model $model, Request $request): void
    {
        $model->addMedia($request->image)->toMediaCollection(config('custom.uploads.collectionName'));
        $image = $model->getFirstMedia(config('custom.uploads.collectionName'));
        $model->update(['image_id' => $image->id]);
    }

    protected function saveRecord(Model $model, Request $request, string $resource, $returnModel = false): JsonResponse|Model
    {
        try {
            DB::beginTransaction();
            $model->fill(self::getRequestData($request, $model::getStaticTableColumns()))->save();
            if ($request->has('image') && !is_null($request->image)) {
                self::uploadImage($model->fresh(), $request);
            }
            DB::commit();
            if ($returnModel) {
                return $model->fresh();
            }
            return $this->success(
                new $resource($model->fresh()),
                Response::HTTP_CREATED,
                class_basename($model) . ' successfully created'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function updateRecord(Model $model, Request $request, string $resource, $returnModel = false): JsonResponse|Model
    {
        try {
            DB::beginTransaction();
            $model->update(self::getRequestData($request, $model::getStaticTableColumns()));
            if ($request->has('image') && !is_null($request->image)) {
                self::uploadImage($model->fresh(), $request);
            }
            DB::commit();
            if ($returnModel) {
                return $model->fresh();
            }
            return $this->success(
                new $resource($model->fresh()),
                Response::HTTP_OK,
                class_basename($model) . ' successfully updated'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    protected function destroyRecord(Model $model): JsonResponse
    {
        try {
            DB::beginTransaction();
            $model->delete();
            DB::commit();
            return $this->success(
                null,
                Response::HTTP_NO_CONTENT,
                class_basename($model) . ' successfully deleted'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Redirect students based on profile status.
     *
     * @return RedirectResponse|null
     */
    protected function redirectStudents(): ?RedirectResponse
    {
        $user = request()->user();

        if ($user->hasRole(RoleEnum::STUDENT)) {
            if (!$user->has_student_profile) {
                return to_route('portal.application', compact('user'));
            }
            return to_route('portal.index', compact('user'));
        }
        return null;
    }
}
