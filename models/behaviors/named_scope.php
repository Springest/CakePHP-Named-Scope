<?php
// basic code taken and modified/fixed from https://github.com/netguru/namedscopebehavior

class NamedScopeBehavior extends ModelBehavior {
    var $cachedScopes = array();

    static $__settings = array();

    function setup(&$model, $settings = array()) {
        self::$__settings[$model->name] = $settings;
    }

    function beforeFind(&$model, $queryData) {
        $scopes = $this->cachedScopes;
        // passed as scopes
        if (!empty($queryData['scopes'])) {
            $scope = !is_array($queryData['scopes']) ? array($queryData['scopes']) : $queryData['scopes'];
            $scopes = am($scopes, $scope);
        }
        // passed as conditions['scopes']
        if (is_array($queryData['conditions']) && !empty($queryData['conditions']['scopes'])) {
            $scope = !is_array($queryData['conditions']['scopes']) ? array($queryData['conditions']['scopes']) : $queryData['conditions']['scopes'];
            unset($queryData['conditions']['scopes']);
            $scopes = am($scopes, $scope);
        }

        // if there are scopes defined, we need to get rid of possible condition set earlier by find() method if model->id was set
        if (!empty($scopes) && !empty($model->id) &&  !empty($queryData['conditions']["`{$model->alias}`.`{$model->primaryKey}`"]) && $queryData['conditions']["`{$model->alias}`.`{$model->primaryKey}`"] == $model->id) {
            unset($queryData['conditions']["`{$model->alias}`.`{$model->primaryKey}`"]);
        }

        $queryData['conditions'][] = $this->_conditions($scopes, $model->name);
        return $queryData;
    }

    function afterFind(&$model, $results, $primary) {
        $this->cachedScopes = array();
    }

    function cacheScope(&$model, $name) {
        $this->cachedScopes[] = $name;
    }

    function _conditions($scopes = array(), $modelName = '') {
        if (!is_array($scopes)) {
            $scopes = array($scopes);
        }
        $_conditions = array();
        foreach ($scopes as $scope) {
            if (strpos($scope, '.')) {
                list($scopeModel, $scope) = explode('.', $scope);
            } else {
                $scopeModel = $modelName;
            }
            if (!empty(self::$__settings[$scopeModel][$scope])) {
                $_conditions[] = array(self::$__settings[$scopeModel][$scope]);
            }
        }

        return $_conditions;
    }
}