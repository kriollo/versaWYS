<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use app\models\Users;
use Throwable;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;

class EmpresasController extends GlobalControllers
{
    private string $pathLogo = '';

    public function __construct()
    {
        global $twig, $session, $config;
        parent::__construct($twig, $session);
        $this->pathLogo = (string) $config['assets']['dashboard']['store']['dist'];
    }

    // metodo para renderizar desde twig por defecto
    public function index(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => 'dashboard/js/empresa/dashEmpresa',
        ]);
    }

    // API
    public function getEmpresasPaginated(): array
    {
        global $request;
        $params = $this->getParamsPaginate($request, ['rut', 'empresas.nombre', 'email'], allowedFilters: [
            'estado' => 'empresas.estado',
            'empresas.estado',
        ]);
        $filter = $params['filter'] ?? '';
        $order = $params['order'] ?? 'id DESC';
        $modelo = new Models\Empresas();
        $result = $modelo->getEmpresasPaginated(
            [
                'empresas.id',
                'rut',
                'empresas.nombre',
                'telefono',
                'email',
                'empresas.estado',
                'token_empresa',
            ],
            $filter,
            $order,
            $params['limitNumber'],
            $params['offset'],
            $this->pathLogo,
            $params['bindings'],
        );
        $count = (int) $result['total'];

        $total_pages = ceil($count / $params['per_page']);
        if ($total_pages === 1 || $total_pages === 0) {
            $total_pages = 1;
            if ($params['page'] >= 1) {
                $params['page'] = 0;
            }
        }

        $columns = [
            ['field' => 'id', 'title' => 'ID', 'export' => true],
            ['field' => 'rut', 'title' => 'Rut', 'export' => true],
            ['field' => 'nombre', 'title' => 'Nombre', 'export' => true],
            ['field' => 'telefono', 'title' => 'Teléfono', 'export' => true],
            ['field' => 'email', 'title' => 'Email', 'export' => true],
            ['field' => 'estado', 'title' => 'Estado', 'type' => 'status', 'export' => true],
            [
                'field' => 'actions',
                'title' => 'Acciones',
                'type' => 'actions',
                'buttons' => [
                    [
                        'title' => 'Editar',
                        'icon' => 'bi bi-pencil-fill text-xl text-yellow-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'editEmpresa',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],

                    [
                        'title' => 'Asociar Módulos',
                        'icon' => 'bi bi-menu-app-fill text-xl text-green-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'showModalAsociaModulo',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Desactivar Empresa',
                        'icon' => 'bi bi-trash-fill text-xl text-red-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Activar Empresa',
                        'icon' => 'bi bi-recycle text-xl text-green-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => '0',
                    ],
                ],
            ],
        ];

        return [
            'success' => 1,
            'data' => $result['data'],
            'columns' => $columns,
            'meta' => [
                'filter' => $params['filtro'],
                'total' => $count,
                'from' => $params['page'] + 1,
                'to' => $params['page'] + count($result['data']),
                'page' => $params['page'],
                'total_pages' => (int) $total_pages,
            ],
        ];
    }

    public function saveEmpresa(): array
    {
        global $request;

        try {
            $params = $request->getAllParams();
            $params['rut'] = str_replace('.', '', $params['rut']);
            $params['nombre'] = Functions::cleanString($params['nombre']);
            if (!isset($params['telefono']) || $params['telefono'] === '') {
                $params['telefono'] = '';
            }

            $params['telefono'] = Functions::cleanString($params['telefono'] ?? '');
            if (!isset($params['email']) || $params['email'] === '') {
                $params['email'] = '';
            }

            $params['email'] = Functions::cleanString($params['email'] ?? '');

            $empresaModel = new Models\Empresas();
            $empresa = $empresaModel->getEmpresaByRut($params['rut']);
            if ($empresa !== null && (int) ($empresa['id'] ?? -1) !== (int) ($params['id'] ?? 0)) {
                return [
                    'success' => 0,
                    'message' => 'Ya existe una empresa con el RUT proporcionado',
                    'empresa' => $empresa,
                ];
            }

            if ($params['action'] === 'create') {
                $modelo = new Users();
                $find = $modelo->findUserByEmail($params['email']);
                if ($find !== []) {
                    return [
                        'success' => 0,
                        'message' => 'Error al registrar',
                        'errors' => [
                            'email' => 'El correo ya existe para otra empresa, por favor ingrese otro',
                        ],
                    ];
                }
            }

            $result = $empresaModel->saveEmpresa($params);

            // crear usuarios admin de la nueva empresa
            $activationUrl = null;
            if ($params['action'] === 'create') {
                $newPerfil = (new Models\Perfil())->savePerfil([
                    'id_empresa' => $result,
                    'nombre' => 'Admin',
                    'estado' => true,
                ]);

                $userModel = new Users();
                $userModel->createUser([
                    'empresa' => $result,
                    'name' => 'Admin ' . $params['nombre'],
                    'email' => $params['email'],
                    // Contraseña aleatoria que nadie conoce: el admin define la suya con el enlace de activación
                    'password' => Functions::hash(bin2hex(random_bytes(32))),
                    'expiration_pass' => date('Y-m-d H:i:s', strtotime('-1 day')),
                    'role' => 'admin',
                    'status' => true,
                    'perfil' => 'definido',
                    'id_perfil' => $newPerfil,
                    'empresa_id' => $result,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                if ($params['email'] !== '') {
                    global $config;
                    $token = Users::newResetToken(7 * 86400);
                    $userModel->saveTokenResetPass($params['email'], $token);
                    $activationUrl = sprintf('%s/%s/reset-password?token=%s', $request->getBaseUrl(), $config['admin']['custom_url'], $token);
                }
            }

            return [
                'success' => 1,
                'message' => $activationUrl === null
                    ? 'Empresa guardada correctamente'
                    : 'Empresa guardada. Enlace de activación del administrador (válido 7 días): ' . $activationUrl,
                'id' => $result,
                'activation_url' => $activationUrl,
            ];
        } catch (Throwable $throwable) {
            return [
                'success' => 0,
                'message' => 'Error al guardar la empresa',
                'errors' => $throwable->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function changeStatus(): array
    {
        global $request;

        try {
            $params = $request->getAllParams();
            $params['id'] = (int) $params['id'];
            $params['estado'] = (int) $params['estado'];

            $empresaModel = new Models\Empresas();
            $result = $empresaModel->changeStatus($params);
            if ($result === 0) {
                return [
                    'success' => 0,
                    'message' => 'No se pudo cambiar el estado de la empresa',
                    'code' => 500,
                ];
            }

            return [
                'success' => 1,
                'message' => 'Estado de la empresa actualizado correctamente',
            ];
        } catch (Throwable $throwable) {
            return [
                'success' => 0,
                'message' => 'Error al cambiar el estado de la empresa',
                'errors' => $throwable->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function getModulesEmpresa(): array
    {
        global $request;

        $idEmpresa = (int) $request->get('idEmpresa', 0);

        $empresaModel = new Models\Empresas();
        $modules = $empresaModel->getModulesByEmpresa($idEmpresa);

        if ($modules === null) {
            return [
                'success' => 0,
                'message' => 'No se encontraron módulos para la empresa',
                'code' => 404,
            ];
        }

        return [
            'success' => 1,
            'data' => $modules,
        ];
    }

    public function saveModuloEmpresaAsociado(): array
    {
        global $request;

        try {
            $params = $request->getAllParams();

            $empresaModel = new Models\Empresas();
            $result = $empresaModel->saveModuloEmpresaAsociado($params);
            if ($result === 0) {
                return [
                    'success' => 0,
                    'message' => 'No se pudo asociar el módulo a la empresa',
                    'code' => 500,
                ];
            }

            return [
                'success' => 1,
                'message' => 'Módulo asociado a la empresa correctamente',
            ];
        } catch (Throwable $throwable) {
            return [
                'success' => 0,
                'message' => 'Error al asociar el módulo a la empresa',
                'errors' => $throwable->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Sube el logo de una empresa y actualiza la columna logo en la tabla empresas.
     * Acepta: image/jpeg, image/png, image/webp — máx 2MB.
     * Nombre destino: {id_empresa}.{ext} en public/dashboard/img/logos/.
     */
    public function uploadLogo(): array
    {
        global $request;

        try {
            $idEmpresa = (int) $request->get('id_empresa', 0);
            if ($idEmpresa === 0) {
                return ['success' => 0, 'message' => 'ID de empresa inválido', 'code' => 400];
            }

            if (empty($_FILES['logo'])) {
                return ['success' => 0, 'message' => 'No se recibió ningún archivo', 'code' => 400];
            }

            $file = $request->file('logo');

            // Validar tipo MIME
            $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($file->getType(), $tiposPermitidos, true)) {
                return [
                    'success' => 0,
                    'message' => 'Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y WebP.',
                    'code' => 422,
                ];
            }

            // Validar tamaño (máx 2MB)
            if ($file->getSize() > (2 * 1024 * 1024)) {
                return ['success' => 0, 'message' => 'El archivo supera el límite de 2MB.', 'code' => 422];
            }

            $ext = $file->getExtension();
            $destFile = $this->pathLogo . '/logos/' . $idEmpresa . '.' . $ext;
            $rutaRel = 'logos/' . $idEmpresa . '.' . $ext;

            $file->moveToNewName($destFile);

            // Actualizar columna logo en la empresa
            (new Models\Empresas())->updateLogo($idEmpresa, $rutaRel);

            return ['success' => 1, 'message' => 'Logo guardado correctamente', 'logo' => $destFile];
        } catch (Throwable $throwable) {
            return [
                'success' => 0,
                'message' => 'Error al subir el logo',
                'errors' => $throwable->getMessage(),
                'code' => 500,
            ];
        }
    }

}
