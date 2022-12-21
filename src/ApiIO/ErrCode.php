<?php

namespace stlswm\MicroserviceAssistant\ApiIO;


/**
 * Class ErrCode
 *系统错误码
 * @package stlswm\MicroserviceAssistant\ApiIO
 */
class ErrCode
{
    const OK             = 0;//成功
    const ParamEmpty     = 1000;//参数为空
    const ParamError     = 1001;//参数错误
    const Business       = 2000;//业务错误
    const RecordNotFound = 2010;//记录不存在
    const TokenErr       = 3000;//Access Token错误
    const SignErr        = 3050;//签名错误
    const ToKenOverdue   = 4001;//Access Token过期
    const NetErr         = 5000;//网络错误
    const NoPower        = 6000;//无权限
    const RecordExists   = 7000;//记录存在
}