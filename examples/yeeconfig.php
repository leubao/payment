<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    'use_sandbox' => false, // 是否使用 微信支付仿真测试系统
    
    //系统商编号=上级商编
    'mch_id'       => '123123123', // 商户id
    //子商户编号
    'sub_mch_id'   => '10025689054',
    //应用标识appkey
    'app_key'      => 'airt_10015386704', // md5 秘钥

    //易宝公钥--正式环境也用此公钥，不用改
    'public_key'   => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6p0XWjscY+gsyqKRhw9MeLsEmhFdBRhT2emOck/F1Omw38ZWhJxh9kDfs5HzFJMrVozgU+SJFDONxs8UB0wMILKRmqfLcfClG9MyCNuJkkfm0HFQv1hRGdOvZPXj3Bckuwa7FrEXBRYUhK7vJ40afumspthmse6bs6mZxNn/mALZ2X07uznOrrc2rk41Y2HftduxZw6T4EmtWuN2x4CZ8gwSyPAW5ZzZJLQ6tZDojBK4GZTAGhnn3bg5bBsBlw2+FLkCQBuDsJVsFPiGh/b6K/+zGTvWyUcu+LUj2MejYQELDO3i2vQXVDk7lVi2/TcUYefvIcssnzsfCfjaorxsuwIDAQAB',
    'private_key'  => 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQClc2biuY3zWbCG6NIukp80E4Srw9HjmqD+NeuJiWC3PEcYt83zZBetydHqNZA3HpOQmAfx+0p/RpH3UWcU0EiCamdDOmoq5byGd23Mk2bwhEExzZPCRVuegaB5Iv6mIZh+FdnCaf2xf/Q91/aAspV2y7eCsVPdSGGxKAzJgt9FcIrAo7P327wYhrCrB0iYLU9FMC3+6H+Ew9OyXl1N+wxm49oGQ8BjZ6YqZ+5CtV/+p3uKLv+5TLV5ZIA+OikoXE62TlWsNGzSQet9D8L8AKOMzrwC4jZGyOBjhcaKlkkh1A4M+Ce669lgHqyHUFDfWSliTrSCuDnBRzd/KokHxQcfAgMBAAECggEASP6bG9hlqkGdwkehw25o0t7xn55rUZF9CercGfgENZNggqVFNeapE8GA3WX4VHkm/Zo1lysY+QI3j/fYFLS36OHs4Ro6kOZ+wIycYq99sQuIf+KFGCblfw8Nr6Qi2UTlNGuLgVyl0tPy+/32AV3I13qVYhG+QFiY0UIsMhONUeLWBayukljys/YwpXEpS1aPyCTQb6dtFJKrlWrHrjacqmteWBmW8cPXczYr4GxI1KhhQfnWbeiKz+dri6I0S4Xz99Cby39xY7HM0R2Ev70l77CNCSDN1H5ZypvkQ1kLssnOElNse/pwtMMH4bImdeSqCD+49JH+DUCpvo9UZvdOgQKBgQD69cNDs1nvSxvdY61xEdEtwwrO0gjA9BIa3jdqVEY0OfDD9ocK2jnuFBKCTm/7jqWwPsH+7kqYGWj8Qe7H3rPlzSvjP0ukzvlmRgctmwzJfCNjwx64FUAZWdShsxEdZIXnD+KMFTtKr8IiwXeiw2/Du+UkCRE6l/wQVj0KhIwGfwKBgQCoxgTBPFkZM/joCQ6YbY19cMBzVxcgpekFjisMLq9rCLOddYoJxusXjsjiRYxD3WBsu4F4N0Oyl/jdQiLCXRlD8GhFlrBAIbZWwnX7mBPVs5xyNh1HtpINxYUrAslD7fz0d6lWBqGNi4OdA8CPlKH7jiMl31JLnLAvJyW+NFrvYQKBgF8hiGqCc0YVd7OdlGK3OU8aj19FGRJjsvVCZUlGNvKXQCBYtGo1vR31t+pzZ1m5gi9kKs/Dbr1nbHerWqOjVRh4hPl4xejsmHffddsg2mEKULQBhASN8aVqewLsyUEWGPg0+lDVv4sZQwM/yWUGprhQ4pSdZ02JzYA34J27DwVrAoGAXXvZtGNGAvTLwVMK95lvDvV+VCUAVYAws3gNFiFh3wqh2uz5OfMp0xGu6c6WJB0iRPgTfdA2ulz9Zykz8a75yK0IRMtz8wH5atMp4ONa0Ts8w/J/g3J4MhKfcbSIYQ0Y2RzS+iiQIcQOcdFbPuyYUKtpgpfRkLpIyMWJXXLIj+ECgYAlqCHtZLHhU6bVvFeoUeL8CSjTGxHDpX5u6lmvCQqRMnB2+O9ArmTRHQyygliMN435TB/Md71gVMqaZ+pfhLvWhaDcc0DmMu1/ywT3MjjzE2uCp0exy0PmRd8C5DUYYgTPOoKtOb/1OBIELTBtYniR9I0lSGYTXmOJTzeGgpYz7g==',
    
    'sign_type'    => 'MD5', // MD5  HMAC-SHA256
    'limit_pay'    => [
        //'no_credit',
    ], // 指定不能使用信用卡支付   不传入，则均可使用
    'fee_type' => 'CNY', // 货币类型  当前仅支持该字段
    
    'notify_url' => 'https://dayutalk.cn/v1/notify/wx',
    
    'redirect_url' => 'https://dayutalk.cn/', // 如果是h5支付，可以设置该值，返回到指定页面
];