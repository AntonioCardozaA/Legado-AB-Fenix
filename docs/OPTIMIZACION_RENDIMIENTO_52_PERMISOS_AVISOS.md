# Corrección 52: prueba de aviso con autorización explícita

El fixture de supervisor esperaba abrir un plan hidráulico sin tener acceso al módulo/área. NotificationRedirectService ya comprueba ambos permisos antes de resolver el enlace; su rechazo era correcto. Se explicó antes de cambiar la prueba.

La prueba ahora comprueba rechazo y aviso sin autorización; luego concede explícitamente los permisos configurables de pasteurizadora y central hidráulica al usuario de fixture y comprueba el enlace original al mismo plan. Se mantiene rol supervisor, filtros de destino y middleware. No se modifica código ni permisos de usuarios operativos.

NotificationControllerTest y NotificationTargetBatchTest pasan: 18 pruebas, 60 aserciones. Es corrección de cobertura, no mejora temporal ni relajación de autorización. El fallo previo correspondiente queda resuelto en la prueba; los tres endpoints LEF ausentes se tratan aparte.
