<?php

namespace WooSignal\LaravelFCM\Domain\UserDevice;

use App\Domain\Shared\Repository;
use App\Domain\UserDevice\UserDevice;

class UserDeviceRepository extends Repository
{	
	public function __construct(UserDevice $model)
	{
		parent::__construct($model);
	}

	public function findByUuid($id)
	{
		return $this->model->where('uuid', $id)->firstOrFail();
	}
}