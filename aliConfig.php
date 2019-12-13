<?php
$config = array (	
		//应用ID,您的APPID。
		'app_id' => "2019090667036050",

		//商户私钥，您的原始格式RSA私钥
		'merchant_private_key' => "MIIEowIBAAKCAQEAhQPCaQeOJmGn483+Iwx3CpKON/tj787lEW9FIny4/UpcmEMRwfSVz6BgQG8tGghSS1ArNBxT1HGEK8xO/Nr7NwZKj3FkxzbePPH5AuiQBCMLANXYBT0RdWzISXbdJ32WXMGcx7XY4HbkjB9D/eNmMdQMHuyREirNbVeX5RNPH4DkEMwZwXOX8ADDMu4ijPRHI/+iAkoAhoUWbkOFRNIUAbfgs78dSWZvjzA6WrqxqJr60ZzrTMTRf9rivE70PsFq4VoyK0Mmrf3jm+zMGyYbwnPk4pjePN6+d2NvuxQJI1SbO3rxRdtOEW3btcVK8krPkXEyRl5z3oWdvI1kJv1lNwIDAQABAoIBAC/lEKWc8NRlKXzfeXjJTjviVY9W179LsjO66xvl6P5bPkgdLlG2bhMa3k2VOpo7ENbJgST8ZjsCvOLAaFinyPkhLMvFutH4scEkJ9OiRekXbSjZ1zTbrmOnzd6WDD/h+rhfhsJqejMo3FaQE31h2jRq9ApWiE4QV3PdrHXxo/XlfqiiXyV3rk8HJI9IR1gByKOSk6XjWAAFKyQtXiGMOwNyqREU5a1WdMCSDFCk3DNEHjxj/zA5RexXCS5ipJzD1l7GMb8qmaSNR9dyXLmNaaE4okxP22MIZ4wDkgO20MrxxvBjnaMi1xQKS0E9bcATdsln+kbGZSeDW/KkzlakwAECgYEA2D/Bcr7ysqwjLqlMzBTWcSL9AEdJRUf/vcius7S0aV4w3w5LafXvQDI8CAQD2ebVBEEjm6ttJCOvpW5jgonacd9zvHKemeSN09/LqkWWrQ36zwXR4Eb6/uZMWLeAppfR4BGjmEgkWODv5gF2ISYn4EhY1Bw9ZqgitDxtKBGhMAECgYEAnXcpmCY1FuvZB0Pz7jaUgpvNra4rWFXrQ9jCKVaM6+JoeThLxS4PFUAyb107cbc5p9fPbhcQZtmLRkV7SGcHF+ARoXgGrPNeF5/mVQSN0GYJTxaTZoHsObcGlA9kNfBk9uSOtOPvCqNRbIrh+XZgsi2TThnOTQuYRTDhleRsFTcCgYBEDHQEdsAKp45+rXnkMp50ha0VvFj7Ozfn8dG/7RpkmeZJGsdydZivG9+2KgVIOZJIv3LEFsLGf5BTP5SCzHx4HvfdkjgEd16GEMOXNkS++ko1gUBVEiEDu1bFCCGsytOZbhOL5Q/DBC2ZtFYHAmnD8yA4xxYvSrN5AhbbaAjQAQKBgBvzV/XC6m+MI7TvcfAZzhi08ThgDx+Z+K3NXwGZdGA2ixbhUEnK2DCMeadc5D1WKazKWO6Qt3+aI+ewU6bRaufpBRglRwISHOSSpH741Pdl9UcNmdJ7Q2QnQcqsRLxyVGmivlYLk5XO0ZgzutLeGWHnRxt45y6z3S6C87f4mNrXAoGBAMQOjwVHMBzCqFurYo1OSCD9xH01Qi99eo3oauOCTqMKzIXySDTtG9u+1H923BV4qjiibWQLhRJDi3KWO/B3kYjEX1PeaB7wiFkRETbY6dXHvjauRLRB0ic9RQ+z/s+cflTm4UWVXUOQF3iZG68BcYdTVYSilh0qwTDzBk0LL4tZ",

        //异步通知地址
		'notify_url' => "",
		
		//同步跳转
		'return_url' => "",

		//编码格式
		'charset' => "UTF-8",

		//签名方式
		'sign_type'=>"RSA2",

		//支付宝网关
		'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
//		'gatewayUrl' => "https://openapi.alipaydev.com/gateway.do",

		//支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
		'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhteihE8vOT2Dj6vMJ975ANu9F8XEMyOY0k9EX9fPpJezbX0/mivJFEgtDp9DwiFq5VNvuCY5qYK6hbARJlxwaKbvau0nCV/3ENPW3Qt9tdZTC3SOblHlHWhllGpHyi2WkOjGWyJ01z3WCJGZpzqFuG+i0B4j+/wXWku4NavJxCLsvSrUUDbIOhUe+prN8fMn9H0hqwSAtxyb2DTptoRuOnkK6CN8SC1YXyXyPiXerv5fNwQcWCoSVOL5zwrbdx9NbwibeaX06s9Oiehk2hWUQgVaMErwFEAo/lr1mw4APxxkJV0q1qI6CQLft+5FtCJEj6IIxrMKgMTV8u8yu3zIlQIDAQAB"
);