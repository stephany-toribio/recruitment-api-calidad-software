import csv
import tempfile
import unittest
from pathlib import Path
from analizar_resultados import analyze, p95

class AnalysisTest(unittest.TestCase):
    def write(self, rows):
        folder = tempfile.TemporaryDirectory();self.addCleanup(folder.cleanup)
        path = Path(folder.name)/'samples.jtl'
        with path.open('w',newline='') as f:
            writer=csv.DictWriter(f,fieldnames=['timeStamp','elapsed','label','responseCode','success','qa_vu','qa_user_id','qa_job_id','qa_app_id'])
            writer.writeheader()
            for i,row in enumerate(rows):
                base=dict(timeStamp=1000+i*100,elapsed=10,label='READ_listar_vacantes',responseCode='200',success='true',qa_vu='postulacion-0',qa_user_id='1',qa_job_id='2',qa_app_id='3')
                base.update(row);writer.writerow(base)
        return path
    def test_empty_is_not_pass(self):
        with self.assertRaises(ValueError):analyze(self.write([]))
    def test_percentile_is_numeric(self):
        self.assertEqual(p95([100,2,30,4000,5]),4000)
        self.assertIsNone(p95([]))
    def test_missing_write_and_login_are_incomplete(self):
        result=analyze(self.write([{}]));self.assertNotEqual(result['performance'],'CUMPLE')
        self.assertEqual(result['overall'],'NO_APROBADO')
    def test_expected_negative_not_an_error_or_latency_write(self):
        result=analyze(self.write([{},dict(label='EXPECTED_401_login',responseCode='401',elapsed=9000)]))
        self.assertEqual(result['expected_4xx'],1);self.assertEqual(result['unexpected_errors'],0)
        self.assertEqual(result['latency']['write']['n'],0)
    def test_expected_case_with_500_is_error(self):
        result=analyze(self.write([dict(label='EXPECTED_409_duplicada',responseCode='500',success='false')]))
        self.assertEqual(result['unexpected_errors'],1);self.assertEqual(result['error_pct'],100)
    def test_regular_write_409_never_counts_as_success(self):
        result=analyze(self.write([dict(label='WRITE_postular',responseCode='409')]))
        self.assertEqual(result['unexpected_errors'],1);self.assertEqual(result['successful_creates'],0)
    def test_failed_verification_is_not_evidence_of_persistence(self):
        result=analyze(self.write([dict(label='WRITE_postular',responseCode='201'),dict(label='READ_verificar_postulacion',success='false')]))
        self.assertEqual(result['creates_without_correlated_get'],1)
        self.assertEqual(result['backend_integrity'],'PENDIENTE_O_FALLIDA')
    def test_duplicate_success_is_detected(self):
        row=dict(label='WRITE_postular',responseCode='201')
        result=analyze(self.write([row,row]));self.assertEqual(result['duplicate_successful_pairs'],1)
    def test_one_percent_is_not_less_than_one(self):
        rows=[dict(label='WRITE_postular',responseCode='201'),dict(label='LOGIN_valido')]+[{} for _ in range(97)]+[dict(success='false')]
        result=analyze(self.write(rows));self.assertEqual(result['error_pct'],1)
        self.assertEqual(result['overall'],'NO_APROBADO')
    def test_failed_write_cannot_be_approved_by_low_error_percentage(self):
        rows=[{},dict(label='LOGIN_valido'),dict(label='WRITE_postular',responseCode='201'),dict(label='WRITE_postular',responseCode='500',success='false')]+[{} for _ in range(200)]
        result=analyze(self.write(rows),{'success':True,'checked':1})
        self.assertLess(result['error_pct'],1)
        self.assertEqual(result['unconfirmed_writes'],1)
        self.assertEqual(result['overall'],'NO_APROBADO')
    def test_global_pass_requires_backend_and_correlated_ids(self):
        rows=[{},dict(label='LOGIN_valido'),dict(label='WRITE_postular',responseCode='201'),dict(label='READ_verificar_postulacion')]
        result=analyze(self.write(rows),{'success':True,'checked':1})
        self.assertEqual(result['overall'],'CUMPLE')
        result=analyze(self.write(rows),{'success':False,'checked':1})
        self.assertEqual(result['overall'],'NO_APROBADO')

if __name__=='__main__':unittest.main(verbosity=2)
