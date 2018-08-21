<?php
namespace Encounting\Jcdh\Models;

/**
 * @property integer              score
 * @property string               name
 * @property string               address
 * @property string               date
 * @property null|JcdhDeduction[] deductions
 */
class JcdhType {
    public $deductions = [];
}