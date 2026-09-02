$p = Start-Process -FilePath 'C:\wamp64\bin\php\php8.3.28\php.exe' -ArgumentList '-S','localhost:8080','-t','.' -RedirectStandardOutput 'C:\wamp64\www\project_man\.freebuff\preview-6073a64f-c4f6-4c3b-887c-93f6e1534f4a.log' -RedirectStandardError 'C:\wamp64\www\project_man\.freebuff\preview-6073a64f-c4f6-4c3b-887c-93f6e1534f4a.log.err' -WindowStyle Hidden -PassThru
Write-Output $p.Id
