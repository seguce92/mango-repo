<?php

namespace LaraChimp\MangoRepo\Concerns;

use Illuminate\Database\Eloquent\Model;
use LaraChimp\MangoRepo\Annotations\EloquentModel;
use LaraChimp\MangoRepo\Doctrine\Annotations\Reader;
use LaraChimp\MangoRepo\Exceptions\InvalidModelException;
use LaraChimp\MangoRepo\Exceptions\UnspecifiedModelException;

trait IsRepositoryBootable
{
    /**
     * Boot the Repository Class.
     *
     * @return self
     */
    public function boot()
    {
        $this->setModel($this->getEloquentModel());

        return $this;
    }

    /**
     * Get the Eloquent Model used by the Repo.
     *
     * @return Model
     */
    protected function getEloquentModel()
    {
        // We first check if Eloquent Model Target
        // is defined in the Repo class const.
        // and if so we resolve using const
        if (defined('static::TARGET')) {
            return $this->getEloquentModelByConst();
        }

        // We set the Model using Annotation.
        return $this->getEloquentModelByAnnotation();
    }

    /**
     * Using the Repositoristy class const to
     * build and set the Eloquent Model
     * to the class.
     *
     * @return Model
     */
    protected function getEloquentModelByConst()
    {
        // Get EloquentModel
        $eloquentModel = app()->make(static::TARGET);

        // Not an instance of Model.
        if (! $eloquentModel instanceof Model) {
            $this->sendInvalidModelException();
        }

        // Return EloquentModel.
        return $eloquentModel;
    }

    /**
     * Using the Repository annotation to
     * build and set the Eloquent Model
     * to the class.
     *
     * @return Model
     */
    protected function getEloquentModelByAnnotation()
    {
        // Get Class Annotations for EloquentModel.
        $classAnnotations = app()->make(Reader::class)->getClassAnnotationsFor($this)->reject(function ($item) {
            return ! ($item instanceof EloquentModel);
        });

        // No EloquentModel annotation class found.
        if ($classAnnotations->isEmpty()) {
            $this->sendUnspecifiedModelException();
        }

        // Get EloquentModel
        $eloquentModel = app()->make($classAnnotations->first()->target);

        // Not an instance of Model.
        if (! $eloquentModel instanceof Model) {
            $this->sendInvalidModelException();
        }

        // Return EloquentModel.
        return $eloquentModel;
    }

    /**
     * We throw this when specified model is Invalid.
     *
     * @throws InvalidModelException
     */
    protected function sendInvalidModelException()
    {
        throw new InvalidModelException('Specified model target for the repository "'.get_class($this).'" is not a valid Eloquent Model instance.');
    }

    /**
     * We throw this when no model is specified on the class.
     *
     * @throws UnspecifiedModelException
     */
    protected function sendUnspecifiedModelException()
    {
        throw new UnspecifiedModelException('No Eloquent Model could be referenced be for "'.get_class($this).'". Specify the Eloquent Model for this repository using "const TARGET" or "@EloquentModel" annotation on the class.');
    }
}
