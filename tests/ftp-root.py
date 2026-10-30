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


if __name__ == '__main__': unittest.main()
