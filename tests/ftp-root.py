import importlib.util
import pathlib
import unittest

spec = importlib.util.spec_from_file_location('preflight', pathlib.Path(__file__).parents[1] / 'tools/check-ftp-root.py')
preflight = importlib.util.module_from_spec(spec)
spec.loader.exec_module(preflight)


class FakeFTP:
    def __init__(self, entries=None, source=b'Plugin Name: LUG Demo\n'):
        self.entries = entries or ['lug-demo.php', 'includes', 'assets']
        self.source = source
    def cwd(self, target): self.target = target
    def nlst(self): return self.entries
    def retrbinary(self, command, callback): callback(self.source)


class BoundaryTests(unittest.TestCase):
    def test_configured_destination(self):
        self.assertEqual(preflight.destination('./plugins/'), './plugins/lug-demo/')
        ftp = FakeFTP()
        preflight.verify(ftp, './plugins/lug-demo/')
        self.assertEqual(ftp.target, './plugins/lug-demo/')
    def test_invalid_paths(self):
        for path in ['', '../plugins', './plugins\n', 'C:\\plugins']:
            with self.assertRaises(ValueError): preflight.destination(path)
    def test_site_root_rejected(self):
        with self.assertRaises(RuntimeError): preflight.verify(FakeFTP(['wp-config.php']), './')
    def test_other_plugin_rejected(self):
        with self.assertRaises(RuntimeError): preflight.verify(FakeFTP(source=b'Plugin Name: Wingspan Optimizer\n'), './')


class TransportTests(unittest.TestCase):
    def test_verified_tls_precedes_credentials_and_data(self):
        events = []
        class FTP:
            def connect(self, host, port): events.append(('connect', host, port))
            def auth(self): events.append('verified-tls')
            def login(self, *args): events.append('login')
            def prot_p(self): events.append('encrypted-data')
        preflight.connect_secure(FTP(), 'fixture', 'fixture')
        self.assertEqual(events[0][2], 21)
        self.assertEqual(events[1:], ['verified-tls', 'login', 'encrypted-data'])

    def test_certificate_failure_prevents_credentials(self):
        import ssl
        events = []
        class FTP:
            def connect(self, *args): events.append('connect')
            def auth(self): raise ssl.SSLCertVerificationError('fixture invalid certificate')
            def login(self, *args): events.append('login')
            def prot_p(self): events.append('encrypted-data')
        with self.assertRaises(ssl.SSLCertVerificationError):
            preflight.connect_secure(FTP(), 'fixture', 'fixture')
        self.assertEqual(events, ['connect'])


if __name__ == '__main__': unittest.main()
