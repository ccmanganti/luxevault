import test from 'node:test';
import assert from 'node:assert/strict';
import { ScanConfidence } from '../../resources/js/scan-confidence.js';
test('requires three identical recent decodes and preserves leading zeros',()=>{const s=new ScanConfidence();assert.equal(s.accept('001234567890',0),false);assert.equal(s.accept('001234567890',170),false);assert.equal(s.accept('001234567890',340),true);assert.equal(s.value,'001234567890');});
test('conflicting reads restart confirmation',()=>{const s=new ScanConfidence();s.accept('ABC123',0);s.accept('ABC128',180);assert.equal(s.accept('ABC123',360),false);assert.equal(s.count,1);});
test('stale, empty and oversized reads never confirm',()=>{const s=new ScanConfidence();s.accept('123',0);s.accept('123',100);assert.equal(s.accept('123',2000),false);assert.equal(s.accept('',2100),false);assert.equal(s.accept('A'.repeat(121),2200),false);});
