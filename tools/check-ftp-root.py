"""Read-only verification of the configured LUG Demo destination."""
import ftplib
import os
import posixpath
import re


def destination(path):
    if not path or any(c in path for c in '\r\n\\') or '..' in path.split('/'):
        raise ValueError('Invalid configured deployment path; no upload permitted.')
    return path.rstrip('/') + '/lug-demo/'


def verify(ftp, target):
    ftp.cwd(target)
    names = {posixpath.basename(item.rstrip('/')) for item in ftp.nlst()}
    if {'wp-config.php', 'wp-admin', 'wp-includes'} & names:
        raise RuntimeError('Destination is a WordPress root; no upload permitted.')
    source = bytearray()
    ftp.retrbinary('RETR lug-demo.php', source.extend)
    if not re.search(rb'Plugin Name:\s*LUG Demo\s*[\r\n]', source):
        raise RuntimeError('Existing LUG Demo source header not verified; no upload permitted.')


if __name__ == '__main__':
    target = destination(os.environ['SFTP_PATH'])
    with ftplib.FTP_TLS('ftp.steelcitylug.com', timeout=30) as ftp:
        ftp.login(os.environ['SFTP_USER'], os.environ['SFTP_PASS'])
        ftp.prot_p()
        verify(ftp, target)
    with open(os.environ['GITHUB_OUTPUT'], 'a', encoding='utf-8') as output:
        output.write('remote=' + target + '\n')
    print('Existing LUG Demo destination verified. No files changed by preflight.')
